import { View      } from 'curvature/base/View';

export class Output extends View
{
	constructor(args = {})
	{
		super(args);

		this.args.max = this.args.max || 256;

		this.buffer = [];
		this.args.lines  = '';

		this.template = `
			<pre class = "output" cv-ref = "tag:curvature/base/Tag">[[lines]]</pre>
		`;

		this.onFrame(()=>{
			this.args.lines = this.buffer.slice().reverse().join("\n");
		});
	}

	clear()
	{
		while(this.buffer.length)
		{
			this.buffer.shift();
		}
	}

	push(line)
	{
		while(this.buffer.length > this.args.max)
		{
			this.buffer.shift();
		}
		
		this.buffer.push(line);
	}
}
