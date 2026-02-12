<!-- 
	This is the billing page, it uses the dashboard layout in: 
	"./layouts/Dashboard.vue" .
	-->

<template>
	<div>

		<a-row type="flex" :gutter="24">

			<!-- Billing Info Column -->
			<a-col :span="24" :md="16">
				<a-row type="flex" :gutter="24">
					<a-col :span="24" :xl="12" class="mb-24">

						<!-- School Payment Summary Card -->
						<CardCredit></CardCredit>
						<!-- / School Payment Summary Card -->

					</a-col>
					<a-col :span="12" :xl="6" class="mb-24" v-for="(stat, index) in paymentStats" :key="index">

						<!-- Payment Statistics Card -->
						<WidgetSalary
							:value="stat.value"
							:prefix="stat.prefix"
							:icon="stat.icon"
							:title="stat.title"
							:content="stat.content"
						></WidgetSalary>
						<!-- / Payment Statistics Card -->

					</a-col>
					<a-col :span="24" class="mb-24">

						<!-- Payment Methods Card -->
						<CardPaymentMethods></CardPaymentMethods>
						<!-- Payment Methods Card -->

					</a-col>
				</a-row>
			</a-col>
			<!-- / Billing Info Column -->
			
			<!-- Invoices Column -->
			<a-col :span="24" :md="8" class="mb-24">

				<!-- Recent Payments Card -->
				<CardInvoices
					:data="recentPayments"
					title="Pagos Recientes"
				></CardInvoices>
				<!-- / Recent Payments Card -->

			</a-col>
			<!-- / Invoices Column -->

		</a-row>

		<a-row type="flex" :gutter="24">

			<!-- Billing Information Column -->
			<a-col :span="24" :md="16" class="mb-24">

				<!-- Parents Information Card -->
				<CardBillingInfo></CardBillingInfo>
				<!-- / Parents Information Card -->

			</a-col>
			<!-- Billing Information Column -->

			<!-- Your Transactions Column -->
			<a-col :span="24" :md="8" class="mb-24">

				<!-- Recent Transactions Card -->
				<CardTransactions
					:data="transactionsData"
				></CardTransactions>
				<!-- / Recent Transactions Card -->

			</a-col>
			<!-- / Your Transactions Column -->
			
		</a-row>

	</div>
</template>

<script>

	import CardCredit from "../components/Cards/CardCredit"
	import WidgetSalary from "../components/Widgets/WidgetSalary"
	import CardPaymentMethods from "../components/Cards/CardPaymentMethods"
	import CardInvoices from "../components/Cards/CardInvoices"
	import CardBillingInfo from "../components/Cards/CardBillingInfo"
	import CardTransactions from "../components/Cards/CardTransactions"

	export default ({
		components: {
			CardCredit,
			WidgetSalary,
			CardPaymentMethods,
			CardInvoices,
			CardBillingInfo,
			CardTransactions,
		},
		data() {
			return {
				paymentStats: [],
				recentPayments: [],
				transactionsData: [],
				loading: false
			}
		},
		mounted() {
			this.loadPaymentData();
		},
		methods: {
			async loadPaymentData() {
				this.loading = true;
				try {
					// Cargar estadísticas
					const statsResponse = await this.$http.get('/api/payments/dashboard/stats');
					this.paymentStats = [
						{
							value: statsResponse.data.total_income,
							prefix: "$",
							icon: `
								<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 22 22">
									<g id="bank" transform="translate(0.75 0.75)">
										<path id="Shape" transform="translate(0.707 9.543)" fill="none" stroke="#fff" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="1.5"/>
										<path id="Path" d="M10.25,0,20.5,9.19H0Z" fill="none" stroke="#fff" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="1.5"/>
										<path id="Path-2" data-name="Path" d="M0,.707H20.5" transform="translate(0 19.793)" fill="none" stroke="#fff" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="1.5"/>
									</g>
								</svg>`,
							title: "Ingresos Totales",
							content: "Pagos recibidos",
						},
						{
							value: statsResponse.data.monthly_income,
							prefix: "$",
							icon: `
								<img src="images/logos/paypal-logo-2.png" alt="">`,
							title: "Ingresos Mensuales",
							content: "Este mes",
						},
						{
							value: statsResponse.data.pending_payments,
							prefix: "",
							icon: `
								<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 22 22">
									<circle cx="11" cy="11" r="8" fill="none" stroke="#fff" stroke-width="2"/>
									<text x="11" y="15" text-anchor="middle" fill="#fff" font-size="12">!</text>
								</svg>`,
							title: "Pagos Pendientes",
							content: "Por procesar",
						}
					];

					// Cargar pagos recientes
					const paymentsResponse = await this.$http.get('/api/payments/recent-transactions');
					this.recentPayments = paymentsResponse.data.map(payment => ({
						title: payment.student?.name || 'Estudiante',
						code: payment.receipt?.receipt_number || `#${payment.id}`,
						amount: payment.paid_amount || payment.amount
					}));

					// Cargar transacciones
					const transactionsResponse = await this.$http.get('/api/payments/recent-transactions');
					this.transactionsData = [
						{ period: "RECIENTES" },
						...transactionsResponse.data.map(transaction => ({
							title: `${transaction.payment_concept?.name || 'Concepto'} - ${transaction.student?.name || 'Estudiante'}`,
							datetime: new Date(transaction.paid_date || transaction.created_at).toLocaleString(),
							amount: transaction.paid_amount,
							type: 1,
							status: 'success'
						}))
					];

				} catch (error) {
					console.error('Error loading payment data:', error);
				} finally {
					this.loading = false;
				}
			}
		}
	})

</script>

<style lang="scss">