window.addEventListener('load', function() {
    //Subscription product edit page.
    const SubscriptionProductSettings = {
        checkBox: document.getElementById('ssfw_subscription_product'),
        subscriptionTab: document.getElementsByClassName('ssfw_subscription_tab_options')[0],
        selectedRadio: document.querySelector('input[type=radio][name=smart_subscription_recurring_expiry]:checked'),
        expiryFields: document.getElementsByClassName('subscription_recurring_expiry_time_fields')[0],
        init: function() {
            this.handleSettingDisplay();
            this.handleSubscriptionExpiry();
            this.checkBox.addEventListener('change', () => this.handleSettingDisplay());
            let expiryRadio = document.getElementsByName('smart_subscription_recurring_expiry');
            for (let i = 0; i < expiryRadio.length; i++) { 
                expiryRadio[i].addEventListener('change', (event) => this.handleSubscriptionExpiry(event.target));
            }
        },
        handleSettingDisplay: function() {
            if (this.checkBox.checked == true){
                this.subscriptionTab.style.display = "block";
              } else {
                this.subscriptionTab.style.display = "none";
            }
        },
        handleSubscriptionExpiry: function(el=false ) {
            let element = this.selectedRadio;
            if( el ) {
                element = el;
            }
            if (element.checked == true && element.value == 'never'){
                this.expiryFields.style.display = "none";
            } else {
                this.expiryFields.style.display = "block";
            }
        }
    }
    SubscriptionProductSettings.init();

 });