import Vue from 'vue';
import Router from 'vue-router';
import Page from './Page';
import ViewComponent from './ViewComponent';

Vue.use(Router);

export default new Router({
	routes: [
		{
			path: '/',
			name: 'Page',
			component: Page,
		},
		{
			path: '/components/:componentName',
			name: 'Component',
			component: ViewComponent,
			props: true,
		},
		{
			path: '/components/:componentName/examples/:exampleName',
			name: 'Component',
			component: ViewComponent,
			props: true,
		},
	],
});
