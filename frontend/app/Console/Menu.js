import { View     } from 'curvature/base/View';
import { Keyboard } from 'curvature/input/Keyboard';

export class Menu extends View
{
	constructor(args = {})
	{
		super(args);

		this.template = require('./MenuTemplate.html');

		this.delayClose = null;
		this.delaySwitch = null;
	}

	postRender()
	{
		this.tags.root.element.addEventListener(
			'mouseover'
			, (event) => {
				if(this.delayClose)
				{
					clearTimeout(this.delayClose);
					this.delayClose = false;
				}

				let lists = this.tags.root.element.querySelectorAll('li');

				lists.forEach((l) => {
					l.classList.remove('closing');
					
					if(l === event.target || l.contains(event.target))
					{
						return;
					}
					
					l.classList.remove('open');
				});

				event.target.classList.add('open');
			}
		);
		this.tags.root.element.addEventListener(
			'mouseleave'
			, (event) => {
				if(this.delayClose)
				{
					clearTimeout(this.delayClose);
					this.delayClose = false;
				}

				// let target = event.target.classList.add('closing');
				let lists = this.tags.root.element.querySelectorAll('li.open');

				lists.forEach((l) => {
					l.classList.add('closing');
				});

				this.delayClose = setTimeout(((lists) =>()=>{
					lists.forEach((l) => {
						l.classList.remove('open');
						l.classList.remove('closing');
					});

				}) (lists), 250);
			}
		);
	}


	click(event)
	{
		let action = event.target.getAttribute('data-action');
		if(action)
		{
			this.call(action);
		}
	}

	mouseover(event)
	{
		let action = event.target.getAttribute('data-hover-action');
		if(action)
		{
			this.call(action);
		}
	}

	call(action)
	{
		let split   = action.split(':');
		let command = split.shift();
		let rest    = split.join(':');

		if(!this.args.input)
		{
			return;
		}

		if(command == 'fill')
		{
			this.args.input.args.val = rest;
			this.args.input.focus();
		}
		else if(command == 'run')
		{
			this.args.input.args.val = rest;
			this.args.input.submit();
		}
	}

	modal(event)
	{
		let type = event.target.getAttribute('data-type');

		if(!this.args.root)
		{
			return;
		}
		
		this.args.root.args.modal = type;

		if(type == 'about')
		{
			this.args.root.args.aboutModal = true;
			this.args.root.args.helpModal = false;
		}
		else if(type == 'help')
		{
			this.args.root.args.aboutModal = false;
			this.args.root.args.helpModal = true;
		}
	}
}
