import { Service as BaseService } from '../Console/Service';
import { Channel } from '../Console/Channel';
import { View } from './View';

export class Service extends BaseService
{
	constructor(socket)
	{
		super(socket);

		let tick = (e,m) => {
			this.view().args.time = new Float64Array(m)[0];
		};

		let timeChannel = 12300;

		socket.subscribe(`message:${timeChannel}`, '12300-12309', tick);

		let tickJson = (e,m) => {
			this.view().args.time = m.time;
		};

		socket.subscribe('message:', 'time:*', tickJson);

		this.cleanup.push( ((tick, tickJson, timeChannel)=> () => {
			socket.unsubscribe(`message:${timeChannel}`, tick);
			socket.unsubscribe('message:',      tickJson);
		})(tick, tickJson, timeChannel));
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