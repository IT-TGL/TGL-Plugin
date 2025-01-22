<?php

class Seasonal_Prices {

   public function __construct() {
        add_shortcode( 'seasonal_prices_rates', [$this, 'show_seasonal_prices_table'] );
        add_shortcode('seasonal_prices_checkout_price', [$this, 'get_checkout_price']);
        add_action( 'wp_enqueue_scripts', [$this, 'enqueue_styles'] );
   }

   public function enqueue_styles() {
        wp_enqueue_style( 'seasonal-prices-table-style', plugin_dir_url( __FILE__ ) . '../assets/css/seasonal-prices-table.css' );
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

   public function show_seasonal_prices_table($atts) {
      if (!function_exists('jet_engine')) {
         return '';
      }
      $seasonalPrices = $this->get_seasonal_prices();
      if (!$seasonalPrices) {
         return 'There are no seasonal rates available for this house.';
      }

      $output = '<table class="seasonal-prices-table"><tbody>';
      foreach ($seasonalPrices as $season) {
         $start_date = !empty($season['startTimestamp']) ? date('m/d/Y', $season['startTimestamp']) : '-';
         $end_date = !empty($season['endTimestamp']) ? date('m/d/Y', $season['endTimestamp']) : '-';
         $price = !empty($season['price']) ? '$' . number_format($season['price'], 0) . '/night' : 'N/A';

         $output .= '<tr>';
         $output .= '<td class="season-title">' . esc_html($season['title'] ?? 'Rates') . '</td>';
         $output .= '<td class="season-dates">from ' . esc_html($start_date) . ' to ' . esc_html($end_date) . '</td>';
         $output .= '<td class="season-price">' . esc_html($price) . '</td>';
         $output .= '</tr>';
      }
      $output .= '</tbody></table>';
      return $output;
   }

   public function get_checkout_price() {
    $seasonalPrices_json = $this->get_seasonal_prices_json();
    $defaultPrice = $this->get_default_price();

    // Obtener informacion de los custom field
    $cleaningFee = get_post_meta(get_the_ID(), 'cleaning_fee', true);
    $cleaningFee = $cleaningFee ? floatval($cleaningFee) : 0;
    $propertyID = get_post_meta(get_the_ID(), 'property-id', true);
    $typeOfBooking = get_post_meta(get_the_ID(), 'type-of-booking', true);

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

            //form fields
            let bookingForm = document.querySelector('#BookingForm');
            let bookingButtonContainer = document.querySelector('#BookingButtonContainer');
            let bookingInput = document.querySelector('input[name="booking_date"]');
            let guests = document.querySelector('#amount_of_guest') ? document.querySelector('#amount_of_guest').value : 1;

            //default Url
            let bookingURL = `https://checkout.lodgify.com/thegoodlifebahamas/${propertyID}/reservation?currency=USD&ref=bnbox`;
            
            //Booking Button
            let bookingButtonHtml = `<button id="bookingButton" href=${bookingURL} class="jet-form-builder__action-button submit-type-reload">
                                        ${typeOfBooking === 'Booking Request' ? 'Request to Book' : 'Book Now'}
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
                    let guestServiceFee = (rentalPrice * 0.07).toFixed(2); 
                    let totalFees = (parseFloat(guestServiceFee) + parseFloat(cleaningFee)).toFixed(2);
                    let grandTotal = (parseFloat(rentalPrice) + parseFloat(totalFees)).toFixed(2);

                    let htmlContent = `
                        <div id="checkout-summary">
                            <div>
                                <strong>Rental</strong> <span class="summary-ammount">$ ${rentalPrice}</span>
                                <div class="summary-details">$ ${defaultPrice} x ${ammountOfDays} nights</div>
                            </div>
                            <div>
                                <strong>Fees</strong> <span class="summary-ammount">$ ${totalFees}</span>
                                <div class="summary-details">Cleaning Fee <span>$ ${cleaningFee.toFixed(2)}</span></div>
                                <div class="summary-details">Guest Service Fee <span>$ ${guestServiceFee}</span></div>
                            </div>
                            <div id="summary-total">
                                <strong>Total (USD)</strong> <span >$ ${grandTotal}</span>
                            </div>
                            <div>Taxes included</div>
                        </div>
                    `;

                    
                    if (bookingForm) {
                        let existingSummary = document.querySelector('#checkout-summary');
                        if (existingSummary) {
                            existingSummary.innerHTML = htmlContent;
                        } else {
                            bookingForm.insertAdjacentHTML('afterend', htmlContent);
                        }
                    }

                    document.getElementById('bookingButton').addEventListener('click', function() {
                        if (checkinDateValue && checkoutDateValue) {
                            bookingURL = `https://checkout.lodgify.com/thegoodlifebahamas/${propertyID}/reservation?currency=USD&ref=bnbox&arrival=${checkinDateValue}&departure=${checkoutDateValue}&guests=${guests}`;
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
    <style>
        #checkout-summary {
            margin-top: 20px;
            color: #666;
            font-size: 16px;
        }
        #checkout-summary div {
            margin-bottom: 20px;
        }
        .summary-ammount {
            color: #000;
        }
        #checkout-summary strong {
            color: #000;
        }
        #checkout-summary span {
            float: right;
            font-size: 16px;
        }
        #summary-total {
            margin-top: 30px;
        }
        #summary-total strong, #summary-total span {
            font-weight: 900;
            color: #31B8C6;
            font-size:22px;
        }
        .summary-details {
            margin-top: 5px;
            margin-left: 5px;
        }
        #bookingButton {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            font-weight: 700;
            border-radius: 6px;
            background-color: #31B8C6;
            color: #fff;
            border: none;
            cursor: pointer;
        }
        #bookingButton:hover {
            background-color: #F28F52;
        }
    </style>
    <?php
    return ob_get_clean();
    
   }

}
