import { View      } from 'curvature/base/View';
import { LoginView } from 'curvature/access/LoginView';
import { Toast     } from 'curvature/toast/Toast';
import { Router    } from 'curvature/base/Router';

// import { NavBar         } from './Ui/NavBar';
// import { Http404        } from './Ui/Http404';
// import { TildePop       } from './Ui/TildePop';

// import { LocationList   } from './Search/LocationList';
// import { SearchFormView } from './Search/SearchFormView';

// import { HomeView     } from './Home/HomeView';

// import { CatchAllView } from './CatchAllView';
// import { ScrollTest   } from './ScrollTest.js';

import { Interpreter } from './Console/Interpreter';
import { Output } from './Console/Output';
import { Input  } from './Console/Input';
import { Menu   } from './Console/Menu';

export class RootView extends View
{
	constructor(args = {})
	{
		super(args);

		this.args.toast = Toast.instance();

		this.args.service = null;
		this.args.output  = new Output();
		this.args.input   = new Input();
		this.args.menu    = new Menu();

		this.args.menu.args.root = this;

		this.args.menu.args.output = this.args.output;
		this.args.menu.args.input  = this.args.input;

		this.args.input.onSubmit(input=>{
			Interpreter.interpret(
				input.args.val
				, this
				, this.args.input
				, this.args.output
				, this.args.menu
			);

			// let output = this.args.output.tags.tag.element;

			// output.scrollTop = output.scrollHeight;
		});

		this.args.output.push('.. Loading init.rc.');

		fetch('/init.rc').then(response=>{
			this.args.output.push('.. init.rc loaded.');
			return response.text();
		}).then(text=>{
			let init = text.split("\n");

			for(let i in init)
			{
				if(!init[i] || /^#/.exec(init[i]))
				{
					continue;
				}

				let match;

				if(match = /^\:\s(.+)/.exec(init[i]))
				{
					this.args.output.push(':: ' + match[1]);
					continue;
				}

				Interpreter.interpret(
					init[i]
					, this
					, this.args.input
					, this.args.output
					, this.args.menu
				);				
			}
		});

		this.args.helpModal
			= this.args.aboutModal
			= this.args.modal
			= false;

		this.template = `
			[[toast]]
			<div class = "subspace" id = "[[_id]]" cv-on = "click:click(event)">
				[[menu]]
				<div class = "services" cv-if = "service">
					<div class = "service-gui">
						[[service]]
					</div>
					<div class = "menu-bar">
						<div class = "title">
							<a cv-on = "click:closeService(event)" class = "subtle-cta">×</a> service
						</div>
						
						<ul class = "menu">
							<li>x</li>
							<li>y</li>
							<li>z</li>
						</ul>
					</div>
				</div>
				[[input]]
				[[output]]
				<div cv-if = "aboutModal">
					<div class = "overlay">
						<div class = "about modal big">
							<h1>about subspace</h1>
							<i>v0.1a</i>
							<br />
							<br />
							Copyright &copy; 2018 Sean Morris.
							<br />						
							All rights reserved.
							<br />
							<button class = "bottom right" cv-on = "click:closeModal(event);">
								ok
							</button>
						</div>
					</div>
				</div>
				<div cv-if = "helpModal">
					<div class = "overlay">
						<div class = "help modal big">
							<h1>subspace help</h1>
							<br />

							<h2>server commands</h2>
							<br>
							<b>echo [STRING]</b> - Echo service.<br />
							<b>time</b> - Time service.<br />
							<b>inc</b> - Increment a counter associated with the user session.<br />
							<b>dec</b> - Decrement a counter associated with the user session.<br />
							<b>sub CHANNEL</b> - Subscribe to a channel.<br />
							<b>pub CHANNEL [MESSAGE]</b> - Publish a message to a channel.<br />
							<b>unsub CHANNEL</b> - Unsubscribe from a channel.<br />
							<b>subs</b> - List your current subscriptions.<br />
							<b>channels</b> - List avaialable channels.<br />
							<b>commands</b> - List avaialable commands.<br />
							<b>nick [NAME]</b> - Get/Set your nickname on the server.<br />
							<b>auth TOKEN</b> - Authorize your connection.<br />
							<br />

							<h2>client control</h2>
							<br>
							<b>/server HOSTNAME[:PORT]</b> - Connect to a server.<br />
							<b>/service SERVICENAME</b> - Invoke a local service.<br />
							<b>/services</b> - List available services.<br />
							<b>/auth</b> - Grab an auth token via ajax and use it to authenticate.<br />
							<b>/pub CHAN [BYTE BYTE...]</b> - Publish raw bytes to a channel on the server.<br />
							<b>/clear</b> - clear the screen.<br />
							<b>/disconnect</b> - close the connection.<br />
							<br />
							<br />
							<br />
							<br />
							
							<button class = "bottom right" cv-on = "click:closeModal(event);">
								ok
							</button>
						</div>
					</div>
				</div>
			</div>
		`;
	}

	click(event)
	{
		if(event.target.matches('pre.output'))
		{
			let sel = window.getSelection();

			if(sel.anchorOffset == sel.focusOffset)
			{
				this.args.input.focus();
			}

			console.log();
		}
	}

	closeModal()
	{
		this.args.helpModal
			= this.args.aboutModal
			= this.args.modal
			= false;
	}

	closeService(event)
	{
		this.args.service.remove();
		this.args.service = null;
	}
}
