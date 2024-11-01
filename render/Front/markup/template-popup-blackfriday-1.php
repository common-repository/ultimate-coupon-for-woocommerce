<div class="ucfw-template-popup ucfw-template-popup-blackfriday-1 ucfw-template-<?php echo wp_kses( $this->colorSet, array() ); ?>-colors">
    <div class="close"></div>
    <!-- Popup Start -->
    <div class="inner-content">
        <!-- Popup Text -->
        <div class="popup-text">
            <?php if ( $this->formattedTitle ) : ?>
            <h1><?php echo wp_kses( $this->formattedTitle, array('br' => array(), 'span' => array('class' => array())) ); ?></h1>
            <?php endif; ?>
            
            <?php if ( $this->description ) : ?>
            <p><?php echo esc_html( $this->description ); ?></p>
            <?php endif; ?>
        </div>

        <?php if ( $this->image ) : ?>
        <div class="popup-image">
            <img src="<?php echo esc_url( $this->image ); ?>" alt="">
        </div>
        <?php endif; ?>
    </div>
    <!-- Popup Button -->
    <div class="btn-area">
        <?php if ( $this->couponCode ) : ?>
        <span class="copy-coupon" data-coupon-code="<?php echo esc_attr( $this->couponCode ); ?>" data-copy-text="<?php echo esc_attr( esc_html__( 'Click to copy', 'ultimate-coupon-for-woocommerce' ) ); ?>" data-copied-text="<?php echo esc_attr( esc_html__( 'Copied!', 'ultimate-coupon-for-woocommerce' ) ); ?>"><?php echo esc_html( $this->couponCode ); ?></span>
        <?php endif; ?>

        <a class="go-shop" href="<?php echo esc_url( $this->url ); ?>"><?php echo esc_html( $this->goToShop ); ?></a>
    </div>
</div>