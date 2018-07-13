import { Service as BaseService } from '../Console/Service';
import { Channel } from '../Console/Channel';
import { View } from './View';

export class Service extends BaseService
{
	constructor(socket)
	{
		super(socket);

		this.view().sock = socket;

		socket.subscribe('message', (e,m) => {

			if('yournick' in m)
			{
				this.view().args.nick = m.yournick;
			}

			if('channels' in m)
			{
				for(let i in m['channels'])
				{
					if(Channel.compareNames(m['channels'][i], 'chat:*'))
					{
						this.view().addChannel(m['channels'][i]);
					}
				}
			}

		});

		socket.subscribe('message:0', (e,m,c,o,i) => {
			console.log(m);
		});
		
		socket.subscribe('message:ping:announce', () => {
			socket.send(`pub ping:announce:pong`);
		});
		
		socket.subscribe('message:ping:announce:pong', (e,m,c,o,i) => {
			this.view().addUser(i, m.nick || i);
		});

		socket.subscribe('message:chat:*', (e,m,c,o,i,oc,p) => {
			if(!p)
			{
				console.log(m);
				return;
			}
			this.view().receiveMessage(p);
		});

		socket.send(`channels`);

		socket.send(`pub ping:announce`);
	}

	view()
	{
		if(!this._view)
		{
			this._view = new View;
		}

		return this._view;
	}
}
