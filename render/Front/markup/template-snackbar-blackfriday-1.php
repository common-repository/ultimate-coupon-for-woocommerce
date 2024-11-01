<div class="ucfw-template-snackbar ucfw-template-snackbar-blackfriday-1 ucfw-template-<?php echo wp_kses( $this->colorSet, array() ); ?>-colors">
    <div class="snack_main">
        <div class="snack_bar">
            <!-- Snack Lr image -->
            <div class="snack_img">
                    <img src="<?php echo UCFW_RESOURCES ?>/images/template-snackbar-blackfriday-1-image.png">
                </div>
            <!-- Snack Content -->
            <div class="snack_content">
                <!-- Sale Image -->
                <div class="sale_img">
                    <img src="<?php echo esc_url( $this->image ); ?>">
                </div>
                <!-- Sale Content Text -->
                <div class="sale_content_text">
                    <h1 class="snackbar-text"><?php echo wp_kses( $this->formattedTitle, array('br' => array(), 'span' => array('class' => array())) ); ?></h1>
                </div>
                <?php if ($this->showCountdown) : ?>
                <!-- Offer area -->
                <div class="offer_limit ucfw-template-countdown">
                    <div class="offer_single_time">
                        <span class="days date_time date_time1">
                            <span>0</span>
                            <span>0</span>
                        </span>
                        <p>DAYS</p>
                    </div>
                    <div class="offer_single_time">
                        <span class="hours date_time date_time2">
                            <span>0</span>
                            <span>0</span>
                        </span>
                        <p>HOURS</p>
                    </div>
                    <div class="offer_single_time">
                        <span class="minutes date_time date_time3">
                            <span>0</span>
                            <span>0</span>
                        </span>
                        <p>MINUTES</p>
                    </div>
                    <div class="offer_single_time">
                        <span class="seconds date_time date_time4">
                            <span>0</span>
                            <span>0</span>
                        </span>
                        <p>SECONDS</p>
                    </div>
                </div>
                <?php endif; ?>
                <!-- Button area -->
                <?php if ($this->goToShop) : ?>
                <div class="grab_btn">
                    <a href="<?php echo esc_url( $this->url ); ?>" class="go-shop"><?php echo esc_html( $this->goToShop ); ?></a>
                </div>
                <?php endif; ?>
            </div>
            <!-- Snack LR Left -->
            <div class="snack_img">
                <img src="<?php echo UCFW_RESOURCES ?>/images/template-snackbar-blackfriday-1-image.png">
            </div>
        </div>
    </div>
</div>