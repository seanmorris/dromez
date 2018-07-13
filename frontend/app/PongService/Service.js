import { Service as BaseService } from '../Console/Service';
import { Channel } from '../Console/Channel';
import { View } from './View';

export class Service extends BaseService
{
	constructor(socket)
	{
		super(socket);

		this.users = {};

		let tick = (e,m,c,o,i) => {
			let frame = new Uint16Array(m);

			if(!(i in this.users))
			{
				this.users[i] = this.view().args.users.length;

				this.view().args.users.push(0);
			}
			else
			{
				this.view().args.users[ this.users[i] ] = frame.join('px,') + 'px';
			}
		};

		let sendMouse = (event)=>{
			
			if(this.view().args.y == event.clientY)
			{
				return;
			}

			this.view().args.y = event.clientY;

			socket.publish(0x500, new Int16Array([
				event.clientX,event.clientY
			]));
		};

		socket.subscribe(`message:${0x0500}`, tick);
		document.addEventListener('mousemove', sendMouse);

		this.cleanup.push( ((tick, sendMouse)=> () => {
			socket.unsubscribe(`message:${0x0500}`, tick);
			document.removeEventListener('mousemove', sendMouse);
		})(tick, sendMouse));
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
