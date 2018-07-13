import { View as ChatView  } from '../ChatService/View';
import { View as ClockView } from '../ClockService/View';

export class Service
{
	constructor(socket)
	{
		this.sock    = socket;
		this.cleanup = [];
	}

	remove()
	{
		while(this.cleanup.length)
		{
			let clean = this.cleanup.pop();
			clean();
		}
	}
}
