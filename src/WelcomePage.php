<?php
/**
 * Welcome Page for CheckoutGlut
 *
 * @package Checkoutglut
 */

namespace CheckoutGlut;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WelcomePage {

	/**
	 * Render the welcome page content
	 */
	public function render_welcome_content() {
		// Check if user can access
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'checkoutglut' ) );
		}
		?>
		<div class="wrap csg-wrap-full checkoutglut-welcome-page">
			<style>
				/* Hide default WP title and add custom header */
				.checkoutglut-welcome-page > h1 {
					display: none;
				}

				.csg-welcome-wrapper {
					max-width: 1000px;
					margin: 20px auto;
					background: #ffffff;
					border-radius: 12px;
					box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
					overflow: hidden;
				}

				.csg-welcome-header {
					background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
					padding: 50px 40px;
					text-align: center;
					color: #ffffff;
				}

				.csg-welcome-logo {
					width: 100px;
					height: 100px;
					background: rgba(255, 255, 255, 0.2);
					border-radius: 24px;
					margin: 0 auto 20px;
					display: flex;
					align-items: center;
					justify-content: center;
				}

				.csg-welcome-logo svg {
					width: 70px;
					height: 70px;
				}

				.csg-welcome-header h1 {
					font-size: 36px;
					font-weight: 700;
					margin: 0 0 10px 0;
					letter-spacing: -0.5px;
					color: #ffffff;
					display: block !important;
				}

				.csg-welcome-subtitle {
					font-size: 18px;
					opacity: 0.95;
					font-weight: 400;
				}

				.csg-welcome-content {
					padding: 40px;
				}

				.csg-welcome-thank-you {
					text-align: center;
					margin-bottom: 40px;
				}

				.csg-welcome-thank-you h2 {
					font-size: 24px;
					color: #1d2327;
					margin: 0 0 12px 0;
					font-weight: 600;
				}

				.csg-welcome-thank-you p {
					font-size: 15px;
					color: #475569;
					line-height: 1.6;
					max-width: 550px;
					margin: 0 auto;
				}

				.csg-welcome-features {
					display: grid;
					grid-template-columns: repeat(3, 1fr);
					gap: 24px;
					margin-bottom: 40px;
				}

				.csg-welcome-feature {
					text-align: center;
					padding: 24px 16px;
					background: #f8fafc;
					border-radius: 12px;
					transition: all 0.2s ease;
					border: 1px solid #e2e8f0;
				}

				.csg-welcome-feature:hover {
					transform: translateY(-3px);
					box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
					border-color: #cbd5e1;
				}

				.csg-welcome-feature-icon {
					width: 50px;
					height: 50px;
					background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
					border-radius: 12px;
					margin: 0 auto 16px;
					display: flex;
					align-items: center;
					justify-content: center;
				}

				.csg-welcome-feature-icon .dashicons {
					font-size: 28px;
					width: 28px;
					height: 28px;
					color: #ffffff;
				}

				.csg-welcome-feature h3 {
					font-size: 16px;
					color: #1d2327;
					margin: 0 0 8px 0;
					font-weight: 600;
				}

				.csg-welcome-feature p {
					font-size: 13px;
					color: #64748b;
					line-height: 1.5;
					margin: 0;
				}

				.csg-welcome-quick-start {
					background: #fef3c7;
					border: 1px solid #fcd34d;
					border-radius: 12px;
					padding: 24px;
					margin-bottom: 30px;
				}

				.csg-welcome-quick-start h3 {
					font-size: 18px;
					color: #92400e;
					margin: 0 0 16px 0;
					display: flex;
					align-items: center;
					gap: 8px;
				}

				.csg-welcome-quick-start h3 .dashicons {
					font-size: 22px;
				}

				.csg-welcome-quick-start ol {
					margin: 0 0 0 20px;
					padding: 0;
				}

				.csg-welcome-quick-start li {
					font-size: 14px;
					color: #78350f;
					margin-bottom: 10px;
					line-height: 1.5;
				}

				.csg-welcome-quick-start li:last-child {
					margin-bottom: 0;
				}

				.csg-welcome-quick-start code {
					background: #fffbeb;
					padding: 2px 6px;
					border-radius: 4px;
					font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
					font-size: 12px;
					color: #b45309;
					border: 1px solid #fde68a;
				}

				.csg-welcome-actions {
					display: flex;
					gap: 16px;
					justify-content: center;
				}

				.csg-welcome-btn {
					display: inline-flex;
					align-items: center;
					gap: 8px;
					padding: 12px 24px;
					font-size: 14px;
					font-weight: 600;
					border-radius: 8px;
					text-decoration: none;
					transition: all 0.2s ease;
				}

				.csg-welcome-btn--primary {
					background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
					color: #ffffff;
					box-shadow: 0 2px 8px rgba(17, 153, 142, 0.25);
				}

				.csg-welcome-btn--primary:hover {
					transform: translateY(-1px);
					box-shadow: 0 4px 12px rgba(17, 153, 142, 0.35);
				}

				.csg-welcome-btn--secondary {
					background: #f1f5f9;
					color: #475569;
					border: 1px solid #e2e8f0;
				}

				.csg-welcome-btn--secondary:hover {
					background: #e2e8f0;
					border-color: #cbd5e1;
				}

				.csg-welcome-btn .dashicons {
					font-size: 16px;
					width: 16px;
					height: 16px;
				}

				@media (max-width: 1200px) {
					.csg-welcome-wrapper {
						margin: 0;
						border-radius: 0;
					}
				}

				@media (max-width: 782px) {
					.csg-welcome-features {
						grid-template-columns: 1fr;
					}

					.csg-welcome-header {
						padding: 40px 24px;
					}

					.csg-welcome-content {
						padding: 24px;
					}

					.csg-welcome-header h1 {
						font-size: 28px;
					}

					.csg-welcome-actions {
						flex-direction: column;
					}

					.csg-welcome-btn {
						width: 100%;
						justify-content: center;
					}
				}
			</style>

			<div class="csg-welcome-wrapper">
				<div class="csg-welcome-header">
					<div class="csg-welcome-logo">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none">
							<path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z" fill="white"/>
						</svg>
					</div>
					<h1><?php esc_html_e( 'Welcome to CheckoutGlut', 'checkoutglut' ); ?></h1>
					<p class="csg-welcome-subtitle"><?php esc_html_e( 'Powerful Checkout Field Editor for WooCommerce', 'checkoutglut' ); ?></p>
				</div>

				<div class="csg-welcome-content">
					<div class="csg-welcome-thank-you">
						<h2><?php esc_html_e( 'Thank you for installing CheckoutGlut!', 'checkoutglut' ); ?></h2>
						<p><?php esc_html_e( 'You\'re just a few steps away from creating a customized checkout experience for your customers.', 'checkoutglut' ); ?></p>
					</div>

					<div class="csg-welcome-features">
						<div class="csg-welcome-feature">
							<div class="csg-welcome-feature-icon">
								<span class="dashicons dashicons-edit"></span>
							</div>
							<h3><?php esc_html_e( 'Custom Fields', 'checkoutglut' ); ?></h3>
							<p><?php esc_html_e( 'Add, edit, and delete custom fields in both classic and block checkout.', 'checkoutglut' ); ?></p>
						</div>

						<div class="csg-welcome-feature">
							<div class="csg-welcome-feature-icon">
								<span class="dashicons dashicons-sort"></span>
							</div>
							<h3><?php esc_html_e( 'Field Reordering', 'checkoutglut' ); ?></h3>
							<p><?php esc_html_e( 'Drag and drop fields to rearrange the checkout flow to your needs.', 'checkoutglut' ); ?></p>
						</div>

						<div class="csg-welcome-feature">
							<div class="csg-welcome-feature-icon">
								<span class="dashicons dashicons-yes-alt"></span>
							</div>
							<h3><?php esc_html_e( 'Validation', 'checkoutglut' ); ?></h3>
							<p><?php esc_html_e( 'Set required fields, validation rules, and conditional logic for fields.', 'checkoutglut' ); ?></p>
						</div>
					</div>

					<div class="csg-welcome-quick-start">
						<h3>
							<span class="dashicons dashicons-superhero-alt"></span>
							<?php esc_html_e( 'Quick Start Guide', 'checkoutglut' ); ?>
						</h3>
						<ol>
							<li><?php esc_html_e( 'Navigate to the Checkout Fields page to manage your checkout fields', 'checkoutglut' ); ?></li>
							<li><?php esc_html_e( 'Add new fields by clicking the "Add Field" button and choose field type', 'checkoutglut' ); ?></li>
							<li><?php esc_html_e( 'Drag and drop fields to reorder them in the checkout flow', 'checkoutglut' ); ?></li>
							<li><?php esc_html_e( 'Configure field properties like label, placeholder, validation, and more', 'checkoutglut' ); ?></li>
							<li><?php esc_html_e( 'Enable or disable fields and test your checkout experience', 'checkoutglut' ); ?></li>
						</ol>
					</div>

					<div class="csg-welcome-actions">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . CHECKOUTGLUT_MENU_SLUG ) ); ?>" class="csg-welcome-btn csg-welcome-btn--primary">
							<span class="dashicons dashicons-arrow-right-alt"></span>
							<?php esc_html_e( 'Get Started', 'checkoutglut' ); ?>
						</a>
						<a href="https://documentation.appglut.com/?utm_source=checkoutglut-welcome&utm_medium=referral&utm_campaign=welcome" target="_blank" class="csg-welcome-btn csg-welcome-btn--secondary">
							<span class="dashicons dashicons-book"></span>
							<?php esc_html_e( 'View Documentation', 'checkoutglut' ); ?>
						</a>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	public static function get_instance() {
		static $instance;
		if ( is_null( $instance ) ) {
			$instance = new self();
		}
		return $instance;
	}
}
