import { Channel } from './Channel';
import { Service } from './Service';

export class Socket
{
	static get(url, refresh = false)
	{
		if(!this.sockets)
		{
			this.sockets = {};
		}

		if(1 || refresh || !this.sockets[url])
		{
			this.sockets[url] = new this(
				new WebSocket(url)
			);
		}

		return this.sockets[url];
	}

	constructor(socket)
	{
		this.socket        = socket;
		socket.binaryType = 'arraybuffer';
		this.data          = {};
		this.listenerCount = {};
		this.openQueue     = [];
		this._onSend       = [];
	}

	subscribe(type, wildType, callback)
	{
		let splitType = type.split(':');
		let mainType  = splitType.shift();
		let channel   = splitType.join(':');

		if(wildType instanceof Function)
		{
			callback = wildType;
			wildType = channel;
		}

		if(channel)
		{
			if(!(channel in this.listenerCount))
			{
				this.listenerCount[channel] = 0;
			}

			this.listenerCount[channel]++;

			this.send(`sub ${channel}`);
		}

		let finalCallback = ((mainType, wildType, channel, callback) => (event) => {

			let packet = {};

			try
			{
				if(typeof event.data == 'string')
				{
					packet = JSON.parse(event.data);
				}
				else if(event.data instanceof ArrayBuffer)
				{
					let channelNumber = new Uint16Array(event.data, 4, 1)[0];

					if(!wildType || Channel.compareNames(wildType, channelNumber))
					{
						callback(
							event
							, event.data.slice(6)
							, channelNumber
							, new Uint16Array(event.data, 0, 1)[0]
								? 'user'
								: 'server'
							, new Uint16Array(event.data, 2, 1)[0]
							, null
							, {}
						);
						return;
					}
				}
				else if(mainType !== 'message')
				{
					callback(event);
					return;
				}
			}
			catch(e)
			{
				if(mainType !== 'message')
				{
					callback(event);
				}
				return;
			}

			if(!wildType)
			{
				callback(
					event
					, packet.message
					, null
					, packet.origin
					, packet.originId
					, null
					, packet
				);
			}

			if(wildType && ('channel' in packet))
			{
				if(Channel.compareNames(wildType, packet.channel))
				{
					callback(
						event
						, packet.message
						, packet.channel
						, packet.origin
						, packet.originId
						, packet.originalChannel
						, packet
					);
				}
			}

		})(mainType, wildType, channel, callback);

		this.socket.addEventListener(mainType, finalCallback);

		return finalCallback;
	}

	unsubscribe(type, callback)
	{
		let splitType = type.split(':');
		let mainType  = splitType.shift();
		let channel   = splitType.join(':');

		if(!channel)
		{
			return;
		}

		this.listenerCount[channel]--;

		if(channel in this.listenerCount && this.listenerCount[channel] > 0)
		{
			
		}
		else
		{
			this.socket.removeEventListener(mainType, callback);
			this.send(`unsub ${channel}`);
		}
	}

	publish(channel, message)
	{
		if(channel == parseInt(channel))
		{
			
			if(message.byteLength)
			{
				message = new Uint8Array(message.buffer);

			}
			else if(!Array.isArray(message))
			{
				message = [message];
			}

			let channelBytes = new Uint8Array(
				new Uint16Array([channel]).buffer
			);

			let bytes = [];

			for(let i in channelBytes)
			{
				bytes[i] = channelBytes[i];
			}

			for(let i = 0; i < message.length; i++)
			{
				bytes[i + 2] = message[i];
			}
			
			this.send(new Uint8Array(bytes));

			return;
		}

		this.send(`pub ${channel} ${message}`);
	}

	send(message)
	{
		if(this.socket.readyState === this.socket.CONNECTING)
		{
			let connectionOpened = ((c) => (event) => {
				while(this.openQueue.length)
				{
					this.send(this.openQueue.shift());
				}

				this.socket.removeEventListener('open', c);
			})(connectionOpened);

			this.socket.addEventListener('open', connectionOpened);

			this.openQueue.unshift(message);

			return;
		}	

		for(let i in this._onSend)
		{
			this._onSend[i](message);
		}

		this.socket.send(message);
	}

	onSend(callback)
	{
		this._onSend.push(callback);
	}

	close(message)
	{
		this.socket.close();
	}

	ping()
	{
		// this.socket.ping();
	}

	pong()
	{
		// this.socket.pong();
	}
}
