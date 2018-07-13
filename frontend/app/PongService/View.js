import { View as BaseView } from 'curvature/base/View';

export class View extends BaseView
{
	constructor(args = {})
	{
		super(args);

		this.args.users = [];

		this.template = require('./Template.html');
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
