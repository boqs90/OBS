<template>
	
	<!-- Layout Footer -->
	<a-layout-footer class="footer-strong">
		<a-row type="flex" align="middle">
			<a-col :span="24">

				<!-- Footer Copyright Notice -->
				<p class="copyright">
					© {{ currentYear }} Soluciones Inteligentes. Todos los derechos reservados.
				</p>
				<!-- / Footer Copyright Notice -->

			</a-col>
		</a-row>
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

<style scoped>
.footer-strong {
  background: #111827; /* oscuro sólido */
  color: #ffffff;
}

.footer-strong .copyright,
.footer-strong .copyright a {
  color: #ffffff;
}

.copyright {
	margin: 0;
	text-align: center !important;
	width: 100%;
}
</style>
