import { View     } from 'curvature/base/View';
import { Keyboard } from 'curvature/input/Keyboard';

export class Input extends View
{
	constructor(args = {})
	{
		super(args);

		this.history = JSON.parse(localStorage.getItem('subspace:history')) || [];
		this.back    = 0;

		this.submitCallbacks = [];

		this.args.val = '';

		this.keyboard = new Keyboard;

		this.keyboard.keys.bindTo('ArrowUp', (v)=>{
			if(v === 1)
			{
				this.args.val = this.history[ this.history.length - (this.back+1) ];
				console.log(this.back, this.history[ this.history.length - (this.back+1) ]);
				if(this.back < this.history.length-1)
				{
					this.back++;
				}
			}
		});

		this.keyboard.keys.bindTo('ArrowDown', (v)=>{
			if(v === 1)
			{
				console.log(this.back, this.history[ this.history.length - (this.back+1) ]);
				if(this.back > 0)
				{
					this.back--;
					this.args.val = this.history[ this.history.length - (this.back+1) ];
				}
				else
				{
					this.args.val = '';
				}
			}
		});

		this.keyboard.keys.bindTo('Enter', (v)=>{
			if(v === 1 && (
				document.activeElement === this.tags.input.element
				|| !document.activeElement.matches('input')
			)){
				this.submit();
			}
		});

		this.template = `
			<div class = "input">
				&lt;&lt;&nbsp;<input type = "text" cv-bind = "val" cv-ref = "input:curvature/base/Tag" />
			</div>
		`;
	}

	submit()
	{
		if(!this.args.val)
		{
			return;
		}

		for(let i in this.submitCallbacks)
		{
			this.submitCallbacks[i](this);
		}

		if(this.history[ this.history.length - 1 ] !== this.args.val)
		{
			this.history.push(this.args.val);
		}

		this.args.val = '';
		this.back     = 0;

		localStorage.setItem('subspace:history', JSON.stringify(this.history));
	}

	onSubmit(callback)
	{
		this.submitCallbacks.push(callback);
	}

	focus()
	{
		this.tags.input.element.focus();

		console.log(123);
	}
}
