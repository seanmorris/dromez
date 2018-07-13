import { View as BaseView } from 'curvature/base/View';
import { Keyboard } from 'curvature/input/Keyboard';
import { Channel } from '../Console/Channel';

export class View extends BaseView
{
	constructor(args = {})
	{
		super(args);

		this.args.messages     = [];
		
		this.args._users       = {};
		this.args.users        = [];
		
		this.args._channels    = {};
		this.args.channels     = [];
		
		this.args.userBuffer   = '';
		
		this.args._nick        = '';
		this.args.nick         = '';

		this.args.currentChannel  = 'main';

		this.buffers  = {};

		this.template = require('./Template.html');

		this.keyboard = new Keyboard;

		this.keyboard.keys.bindTo('Enter', (v)=>{
			if(v === 1 && document.activeElement === this.tags.input.element)
			{
				this.send();
			}
		});
	}

	receiveMessage(message)
	{
		let user    = message['message']['nick'] || message['originId'];
		let channel = message['channel'];
		let content = message['message']['content'];
		
		this.addChannel(channel);

		this.buffers[channel].push(`[${user}] ${content}`);

		let currentChannel = `chat:${this.args.currentChannel}`;

		let output = this.tags.output.element;

		if(currentChannel === channel)
		{
			this.args.messages = Object.values(
				this.buffers[currentChannel].slice(-100)
			);

			if(output)
			{
				output.scrollTop = output.scrollHeight;
			}
		}
		else
		{
			this.tags.channel[
				this.args._channels[channel]
			].element.classList.add('superbold');
		}
	}

	addUser(id, nick)
	{
		this.args._users[id] = nick || id;

		this.args.users = Object.values(this.args._users);
	}

	addChannel(channel)
	{
		if(Channel.isWildcard(channel))
		{
			return;
		}

		if(channel in this.args._channels)
		{
			return;
		}

		let shortName = Channel.namePart(channel, 1);

		this.buffers[channel] = [];
		this.args._channels[channel] = this.args.channels.length;

		this.args.channels.push(shortName);

		if(this.args.channels.length == 1)
		{
			this.tags.channel[0].element.classList.add('active');
		}
	}

	send()
	{
		this.sock.send(`pub chat:${this.args.currentChannel} ${this.args.userBuffer}`);

		this.args.userBuffer = false;

		this.tags.input.element.focus();
	}

	postRender()
	{
		this.tags.input.element.addEventListener(
			'cvDomAttached'
			, ()=>{this.tags.input.element.focus()}
		);
	}

	clickChannel(channelId)
	{
		this.tags.channel[channelId].element.classList.remove('superbold');

		this.tags.channel.map((tag)=>{
			tag.element.classList.remove('active');
		});

		this.tags.channel[channelId].element.classList.add('active');

		let channel = this.args.channels[channelId];

		console.log(this.args.channels);

		if(!channel)
		{
			return;
		}

		this.args.currentChannel = channel;

		channel = `chat:${channel}`;

		this.addChannel(channel);

		this.args.messages = Object.values(
			this.buffers[channel].slice(-100)
		);
	}

	setNick()
	{
		this.sock.send(`nick ${this.args._nick}`);
		this.sock.send(`pub ping:announce`);
	}
}
