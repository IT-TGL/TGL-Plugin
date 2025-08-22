<?php

class Booking_Form_Price_Calculator {

   public function __construct() {
        add_shortcode('price_calculator', [$this, 'booking_form_price_calculator']);
        add_action( 'wp_enqueue_scripts', [$this, 'enqueue_styles'] );
   }

   public function enqueue_styles() {
        wp_enqueue_style( 'booking-form-price-calculator', plugin_dir_url( __FILE__ ) . '../assets/css/booking-form-price-calculator.css' );
   }

   public function get_default_price() {
      $prices = jet_engine()->listings->data->get_meta('jet_abaf_price');
      return !empty($prices['_apartment_price']) ? json_encode($prices['_apartment_price']) : false;
   }

   public function get_seasonal_prices() {
      $prices = jet_engine()->listings->data->get_meta('jet_abaf_price');
      if (!is_array($prices) || empty($prices['_seasonal_prices']) || !is_array($prices['_seasonal_prices'])) {
         return false;
      }
      return $prices['_seasonal_prices'];
   }

   public function get_seasonal_prices_json() {
      $seasonalPrices = $this->get_seasonal_prices();
      return $seasonalPrices ? json_encode($seasonalPrices) : false;
   }

   public function booking_form_price_calculator() {
    $seasonalPrices_json = $this->get_seasonal_prices_json();
    $defaultPrice = $this->get_default_price();

    // Obtener informacion de los custom field
    $cleaningFee = get_post_meta(get_the_ID(), 'cleaning_fee', true);
    $cleaningFee = $cleaningFee ? floatval($cleaningFee) : 0;
    $propertyID = get_post_meta(get_the_ID(), 'property-id', true);
    $typeOfBooking = get_post_meta(get_the_ID(), 'instant_confirmation', true);
    $damageProtection = get_post_meta(get_the_ID(), 'damage_protection_fee', true);


    // This is used to determine the booking button behavior
    // If TGL_SITE_MODE is not defined, it will default to 'rentals'
    // TGL_SITE_MODE can be 'rentals' or 'travel-agency'
    $site_mode = defined('TGL_SITE_MODE') ? TGL_SITE_MODE : 'rentals';

    ob_start();
    ?>
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {

            let seasonalPrices = <?php echo $seasonalPrices_json ?: '[]'; ?>;
            let defaultPrice = <?php echo $defaultPrice ?: '0'; ?>;

            //metafields
            let cleaningFee = <?php echo $cleaningFee; ?>;
            let propertyID = '<?php echo $propertyID; ?>';
            let typeOfBooking = '<?php echo $typeOfBooking; ?>';
            let damageProtection = '<?php echo $damageProtection; ?>';

            const siteMode = '<?php echo $site_mode; ?>';

            let damageProtectionValue = 50;

            if (damageProtection === 'No') {
                damageProtectionValue = 0;
            }

            //form fields
            let checkoutSummaryTargetElement;
            if (siteMode === 'rentals') {
                checkoutSummaryTargetElement = document.querySelector('#BookingForm');
            } else if (siteMode === 'travel_agents') {
                checkoutSummaryTargetElement = document.querySelector('.AmountOfGuestsInput');
            }

            let bookingButtonContainer = document.querySelector('#BookingButtonContainer');
            let bookingInput = document.querySelector('input[name="booking_date"]');
            let guests = document.querySelector('#amount_of_guest') ? document.querySelector('#amount_of_guest').value : 1;


            //default Url
            let bookingURL = `https://checkout.lodgify.com/thegoodlifebahamas/${propertyID}/reservation?currency=USD&ref=bnbox`;
            
            //Booking Button
            if( siteMode === 'rentals' ) {

                let bookingButtonHtml = `<button id="bookingButton" href=${bookingURL} class="jet-form-builder__action-button submit-type-reload">
                                        ${typeOfBooking === 'Instant Confirmation' ? 'Book Now' : 'Request to Book'}
                                    </button>`;
                                
                bookingButtonContainer.insertAdjacentHTML('afterbegin', bookingButtonHtml);

                let bookingButton = document.getElementById('bookingButton');

                if(bookingButton) {
                    let defaultBookingButton = document.querySelector('#DefaultBookingButton');
                    defaultBookingButton.remove();
                } 

                document.getElementById('bookingButton').addEventListener('click', function() {
                            window.location.href = bookingURL;
                });
            }
            
            // Función para formatear fechas de MM/DD/YYYY a YYYY-MM-DD
            function formatDateToYYYYMMDD(dateString) {
                if (!dateString) return '';
                const parts = dateString.split('/');
                if (parts.length === 3) {
                    const month = parts[0];
                    const day = parts[1];
                    const year = parts[2];
                    return `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
                }
                return dateString; // Retorna sin cambios si el formato no es el esperado
            }

            function updateCheckoutInfo() {
                let dates = bookingInput.value.split(' - ');
                let checkinDateValue = dates[0]; 
                let checkoutDateValue = dates[1]; 

                if (checkinDateValue && checkoutDateValue) {
                    let checkinDate = new Date(checkinDateValue);
                    let checkoutDate = new Date(checkoutDateValue);
                    let totalPrice = 0;
                    let ammountOfDays = 0;

                    for (let date = new Date(checkinDate); date < checkoutDate; date.setDate(date.getDate() + 1)) {
                        let currentTimestamp = date.getTime() / 1000;
                        let matchingSeason = seasonalPrices.find(season => {
                            let startTimestamp = parseInt(season.startTimestamp);
                            let endTimestamp = parseInt(season.endTimestamp);
                            return currentTimestamp >= startTimestamp && currentTimestamp <= endTimestamp;
                        });

                        totalPrice += matchingSeason ? parseFloat(matchingSeason.price) : parseFloat(defaultPrice);
                        ammountOfDays++;
                    }

                    let rentalPrice = totalPrice.toFixed(2);
                    let guestServiceFee = (rentalPrice * 0.077).toFixed(2); // 7.7% service fee
                    let travelAgentCommission = (totalPrice * 0.1).toFixed(2); // 10% commission
                    let totalFees = (parseFloat(guestServiceFee) + parseFloat(cleaningFee)).toFixed(2);
                    let grandTotal = (parseFloat(rentalPrice) + parseFloat(totalFees) + parseFloat(damageProtectionValue)).toFixed(2);

                    let htmlContent = `
                        <div id="checkout-summary">
                            <details>
                                <summary><strong>Rental</strong> <span class="summary-ammount">$ ${rentalPrice}</span></summary>
                                <div class="summary-details">$ ${defaultPrice} x ${ammountOfDays} nights</div>
                            </details>
                            <details>
                                <summary><strong>Fees</strong> <span class="summary-ammount">$ ${totalFees}</span></summary>
                                <div class="summary-details">Cleaning Fee <span>$ ${cleaningFee.toFixed(2)}</span></div>
                                <div class="summary-details">Guest Service Fee <span>$ ${guestServiceFee}</span></div>
                                ${ damageProtectionValue > 0 ? `
                                    <div class="summary-details">Damage Protection Fee <span>$ ${damageProtectionValue}</span></div>
                                    ` :''
                                }
                                ${ siteMode === 'rentals' ? '' : `
                                <div class="summary-details">
                                    <strong>Your Commission</strong> <span class="summary-ammount">$ ${travelAgentCommission}</span>
                                </div>
                                `}
                            </details>
                            
                            <div id="summary-total">
                                <strong>Total (USD)</strong> <span >$ ${grandTotal}</span>
                            </div>
                            <div>Taxes included</div>
                        </div>
                    `;
                    
                    if (checkoutSummaryTargetElement) {
                        let existingSummary = document.querySelector('#checkout-summary');
                        if (existingSummary) {
                            existingSummary.innerHTML = htmlContent;
                        } else {
                            checkoutSummaryTargetElement.insertAdjacentHTML('afterend', htmlContent);
                        }
                    }

                    document.getElementById('bookingButton').addEventListener('click', function() {
                        if (checkinDateValue && checkoutDateValue) {
                            bookingURL = `https://checkout.lodgify.com/thegoodlifebahamas/${propertyID}/reservation?currency=USD&ref=bnbox&arrival=${formatDateToYYYYMMDD(checkinDateValue)}&departure=${formatDateToYYYYMMDD(checkoutDateValue)}&adults=${guests}`;
                        }
                        window.location.href = bookingURL;
                    });
                }
            }

            if (bookingInput) {
                const observer = new MutationObserver(updateCheckoutInfo);
                observer.observe(bookingInput, { attributes: true, attributeFilter: ['value'] });
                updateCheckoutInfo();

            } else {
                console.error('Los campos necesarios no se encontraron.');
            }
        });
    </script>
    <?php
    return ob_get_clean();
    
   }

}

