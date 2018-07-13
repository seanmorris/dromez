import { View as BaseView } from 'curvature/base/View';

export class View extends BaseView
{
	constructor(args = {})
	{
		super(args);

		this.args.time = '';
		
		this.args.hour   = 0;
		this.args.minute = 0;
		this.args.second = 0;

		this.args.bindTo('time', v => {
			if(!v)
			{
				return;
			}

			let date = new Date(v*1000);

			let hour   = date.getHours();
			let minute = date.getMinutes();
			let second = date.getSeconds();
			let ms     = date.getMilliseconds();

			this.args.secondArc = (second + (ms/1000)) / 60 * 360;
			
			this.args.minuteArc = (
				((minute / 60)
					+ second / (60*60)
				) * 360
			);
			
			this.args.hourArc = (
				((hour / 12)
					+ minute / (12*60)
				)* 360
			);

			this.args.hour   = String(hour).padStart(2, '0');
			this.args.minute = String(minute).padStart(2, '0');
			this.args.second = String(second).padStart(2, '0');
		});

		this.template = require('./Template.html');
	}
}
