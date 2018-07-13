import { Tag      } from 'curvature/base/Tag';
import { Router   } from 'curvature/base/Router';
import { RootView } from './RootView';

document.addEventListener('DOMContentLoaded', () => {
	const view = new RootView();
	const body = new Tag(document.querySelector('body'));
	body.clear();
	view.render(body.element);
	Router.listen(view);
});
