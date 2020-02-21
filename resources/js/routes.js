import VueRouter from 'vue-router';

let routes = [
	{
		name: 'Home',
		path: '/home',
		alias: '/',
		component: require('./views/student/Home.vue').default,
	},
	{
		name: 'Meus Dados',
		path: '/meus-dados',
		component: require('./views/student/MyData.vue').default,
	},
	{
		name: 'Ajuste',
		path: '/ajuste',
		component: require('./views/student/adjustment/Adjustment.vue').default,
	},
	{
		name: 'Certificados',
		path: '/certificados',
		component: require('./views/Certificados.vue').default,
	},
	{
		name: 'Eventos',
		path: '/eventos',
		component: require('./views/Eventos.vue').default,
	},
	{
		name: 'Resoler ajustes',
		path: '/resolver-ajustes',
		component: require('./views/servant/adjustment/ResolveAdjustment.vue').default,
	},
];




export default new VueRouter({
	routes
});
