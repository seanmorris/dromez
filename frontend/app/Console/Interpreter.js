import { Socket  } from './Socket';
import { Service as ChatService  } from '../ChatService/Service';
import { Service as ClockService } from '../ClockService/Service';
import { Service as PongService } from '../PongService/Service';

export class Interpreter
{
	static interpret(line, root, input, output, menu)
	{
		let match;
		let services = {
			clock: ClockService
			, chat: ChatService
			, pong: PongService
		};

		if(/^\//.exec(line))
		{
			output.push(',, ' + line);
		}

		if(match = /^\/server\s+(.+)$/.exec(line))
		{
			this.server = match[1];

			let prevClosed = false;

			this.sock = Socket.get(match[1], prevClosed);

			this.sock.subscribe('open', openEvent => {
				output.push('.. Connected!');
			});

			this.sock.subscribe('close', openEvent => {
				output.push('.. Connection closed.');
				this.sock.close();
			});

			this.sock.onSend(message => {
				if(message instanceof Uint8Array)
				{
					output.push('<< 0x ' + Array.from(message).map(
						x=>x.toString(16)
							.toUpperCase()
							.padStart(2, '0')
					).join(' '));
					return;
				}
				output.push('<< ' + message);
			});

			let allMessages = (e,m,c,o,i,oc,p) => {
				if(o == 'server')
				{
					if(m.channels)
					{
						menu.args.channels = m.channels
					}
					else if(m.subscriptions)
					{
						menu.args.subscriptions = m.subscriptions;
					}
					else if(m.commands)
					{
						menu.args.commands = m.commands;
					}
				}

				if(p !== undefined)
				{
					if(m instanceof ArrayBuffer)
					{
						let bytesArray = new Uint8Array(m);
						let user    = i.toString(16)
							.toUpperCase()
							.padStart(4, '0');
						let channel = c.toString(16)
							.toUpperCase()
							.padStart(4, '0');
						let header  = `0x${user}${channel}`;

						if(o == 'server')
						{
							header = `0x${channel}`;
						}

						let bytes = Array.from(bytesArray).map(x=>{
							return x.toString(16)
								.toUpperCase()
								.padStart(2, '0');
						});

						output.push(
							`>> ${header}`
							+ " "
							+ bytes.join(' ')
						);
					}
					else
					{
						output.push('>> ' + JSON.stringify(
							p, null, 4
						));
					}

				}

			};

			this.sock.subscribe('message', allMessages);
		}
		else if(/^\/auth$/.exec(line))
		{
			if(!this.sock)
			{
				output.push('!! Error: connect to a server first.');
			}

			output.push('.. Requesting auth token via AJAX.');

			fetch('//'+this.server+'/auth').then(response=>{
				return response.text();
			}).then(text=>{
				output.push('.. Got auth token.');
				this.sock.send('auth ' + text);
			});
		}
		else if(/^\/disconnect$/.exec(line))
		{
			if(!this.sock)
			{
				output.push('!! Error: connect to a server first.');
			}

			this.sock.close();
		}
		else if(/^\/clear$/.exec(line))
		{
			output.clear();
		}
		else if(match = /^\/pub\s(?:0x)([0-9A-F]{4})\s?(([0-9A-F]{2}\s?)+)?/.exec(line))
		{
			let channel = parseInt(match[1], 16);
			let data    = [];
			if(match[2])
			{
				data = match[2].split(' ');
			}

			console.log(match[2], data);

			let channelBytes = new Uint8Array(
				new Uint16Array([channel]).buffer
			);

			let bytes = [];

			for(let i in channelBytes)
			{
				bytes[i] = channelBytes[i];
			}

			for(let i = 0; i < data.length; i++)
			{
				console.log(i);
				bytes[i + 2] = parseInt(data[i], 16);
			}

			console.log(bytes);

			this.sock.send(new Uint8Array(bytes));
		}
		else if(/^\/service/.exec(line))
		{
			let splitLine = line.split(' ');

			let serviceName = splitLine[1];

			if(root.service)
			{
				root.service.remove();
			}

			if(serviceName in services)
			{
				output.push('.. Loading service: ' + serviceName);

				let serv = new services[serviceName](this.sock);

				root.service = serv;
				root.args.service = serv.view();
			}
			else
			{
				output.push('.. Closing services.');

				root.args.service = null;
			}

		}
		else if(/^\//.exec(line))
		{
			output.push('.. Bad command.');
		}
		else if(this.sock)
		{
			this.sock.send(line);
		}
		else if(!this.sock)
		{
			output.push('!! Error: connect to a server first.');
			return;
		}
	}
}