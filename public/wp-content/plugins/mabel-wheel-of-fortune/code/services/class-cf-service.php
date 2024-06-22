<?php

namespace MABEL_WOF\Code\Services {

	use MABEL_WOF\Core\Common\Managers\Settings_Manager;

	class CF_Service {

		public static function form_html( $wheel_id, $fb_obligated, $allow_duplicates ) {
			$app_id = Settings_Manager::get_setting('chatfuel_app_id');
			$page_id = Settings_Manager::get_setting('chatfuel_page_id');

			?>
			<script>
                window.fbMessengerPlugins = window.fbMessengerPlugins || {
                    init : function() {
                        FB.init({
                            appId: "<?php echo $app_id; ?>",
                            xfbml: true,
                            version: "v2.11"
                        });
                        FB.Event.subscribe('messenger_checkbox', function(e) {
                            if(e.event === 'checkbox') {
                                WOF.Dispatcher.publish('wof-fb-checkbox-'+e.state,{
                                    wheel:e.ref
                                });
                            }
                        });
                    },
                    callable : []
                };
                window.fbMessengerPlugins.callable.push( function(){
                    var ruuid, fbPluginElements = document.querySelectorAll(".fb-messenger-checkbox[page_id='<?php echo $page_id; ?>']");
                    if (fbPluginElements) {
                        for( i = 0; i < fbPluginElements.length; i++ ) {

                            ruuid = 'cf_' + (new Array(16).join().replace(/(.|$)/g, function(){return ((Math.random()*36)|0).toString(36)[Math.random()<.5?"toString":"toUpperCase"]();}));
                            fbPluginElements[i].setAttribute('user_ref', ruuid);
                            fbPluginElements[i].setAttribute('origin', window.location.href);

                            window.wofConfirmOptIn = function() {
                                FB.AppEvents.logEvent('MessengerCheckboxUserConfirmation', null, {
                                    app_id:'<?php echo $app_id; ?>',
                                    page_id:'<?php echo $page_id;?>',
                                    ref:'WP Optin Wheel (<?php echo $wheel_id; ?>)',
                                    user_ref: ruuid
                                });
                            };

                        }
                    }
                });

                window.fbAsyncInit = window.fbAsyncInit || function() {
                    window.fbMessengerPlugins.callable.forEach( function( item ) { item(); } );
                    window.fbMessengerPlugins.init();
                };

                setTimeout( function() {
                    (function(d, s, id){
                        var js, fjs = d.getElementsByTagName(s)[0];
                        if (d.getElementById(id)) { return; }
                        js = d.createElement(s);
                        js.id = id;
                        js.src = "//connect.facebook.net/en_US/sdk.js";
                        fjs.parentNode.insertBefore(js, fjs);
                    }(document, 'script', 'facebook-jssdk'));
                }, 0);

                jQuery(document).ready(function() {

                    var wofFbConfig = {
                        wheelId:            <?php echo $wheel_id; ?>,
                        isObligated:        <?php echo $fb_obligated ? 'true' : 'false'; ?>,
                        allowDuplicates:    <?php echo $allow_duplicates ? 'true' : 'false'; ?>,
                        isChecked:          false
                    };

                    WOF.Dispatcher.subscribe('wof-fb-checkbox-checked',function(data) {
                        if(data.wheel == wofFbConfig.wheelId )
                            wofFbConfig.isChecked = true;
                    });

                    WOF.Dispatcher.subscribe('wof-fb-checkbox-unchecked',function(data) {
                        if(data.wheel == wofFbConfig.wheelId )
                            wofFbConfig.isChecked = false;
                    });

                    WOF.Dispatcher.subscribe('wof-after-game-start', function(wheel){
                        if(wheel.id == wofFbConfig.wheelId) {
                            if(wofFbConfig.isChecked)
                                window.wofConfirmOptIn();
                        }
                    });

                    WOF.Dispatcher.addFilter('wof-can-optin',function(wheel) {
                        if(wheel.id == wofFbConfig.wheelId) {
                            if(!wofFbConfig.isObligated)
                                return true;

                            if(!wofFbConfig.isChecked)
                                wheel.element.find('.wof-fb-checkbox').addClass('wof-form-error do-form-nudge');

                            return wofFbConfig.isChecked;
                        }

                        return true;

                    });

                });
			</script>
			<div class="wof-fb-checkbox" style="background: white; display: block; border-radius: 4px;">
				<div
					class="fb-messenger-checkbox"
					origin=""
					page_id="<?php echo $page_id; ?>"
					messenger_app_id="<?php echo $app_id; ?>"
					user_ref=""
					ref="<?php echo $wheel_id ?>"
					prechecked="false"
					allow_login="true"
					size="large">

				</div>
			</div>
			<?php
		}

	}
}