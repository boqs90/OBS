<template>

	<!-- Layout Footer -->
	<a-layout-footer>
		
		<!-- Copyright Notice -->
		<p class="copyright">
			© {{ currentYear }} Soluciones Inteligentes. Todos los derechos reservados.
		</p>
		<!-- / Copyright Notice -->

	</a-layout-footer>
	<!-- / Layout Footer -->

</template>

<script>

	export default ({
		data() {
			return {
				currentYear: new Date().getFullYear(),
			}
		},
	})

</script>

<style lang="scss" scoped>

	.nav-link svg {
		margin-right: 5px;
		vertical-align: middle;
	}
	.nav-link span {
		vertical-align: middle;
	}
	.ant-menu-submenu-popup {
		width: 100%;
	}
	.copyright {
		margin: 0;
		text-align: center !important;
		width: 100%;
	}

</style>