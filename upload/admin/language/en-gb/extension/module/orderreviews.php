<?php
$_['heading_title']              = 'OrderReviews';
$_['error_permission']           = 'Warning: You do not have permission to modify module OrderReviews!';
$_['text_success']               = 'Success: You have modified module OrderReviews!';
$_['text_enabled']               = 'Enabled';
$_['text_disabled']              = 'Disabled';
$_['button_cancel']              = 'Cancel';
$_['save_changes']               = 'Save changes';
$_['text_default']               = 'Default';
$_['text_module']                = 'Modules';
$_['text_extension']             = 'Extensions';
$_['text_percentage']            = 'Percentage';

$_['text_fixed']                 = 'Fixed amount';
$_['text_nod_disc']              = 'No discount';
$_['text_multilingual']          = 'Multi-lingual settings:';
$_['text_expired_coupons']       = 'Remove Expired Coupons:';
$_['text_expired_coupons_help']  = 'Remove all expired coupons created from the module.<br /><strong>NOTE:</strong> It does not matter if the coupons were used or not. All expired coupons which were generated from the module will be removed.';
$_['btn_clear_expired_coupons']  = 'Clean up the Coupons!';
$_['text_email_type']            = 'Type of review mail:';
$_['text_email_type_help']       = 'Choose whether to send the full review form or just a link to it on your site. Choose the link option if the review form is not displayed well.';
$_['text_send_form']             = 'Send form';
$_['text_send_link']             = 'Send link';
$_['text_cron_job']              = 'Cron job:';
$_['text_cron_job_help']         = 'Send automatically emails to the customers.';
$_['text_how_to_setup']          = 'How to set up the cron job?';
$_['text_notification_option']   = 'Receive notification email when the cron is executed.';
$_['text_bcc']                   = 'Send BCC to store owner:';
$_['text_bcc_help']              = 'Enabling this option will add {e_mail} as BCC recepient.';
$_['review_name']                = 'Set the name of the template which will show up on the left column.';
$_['text_order_status']          = 'Order status:';
$_['text_order_status_help']     = 'Define the order status for the selected mail review.';
$_['text_select_order_status']   = 'Select order status';
$_['text_customer_group']        = 'Customer group:';
$_['text_customer_group_help']   = 'Specify customer group for the selected mail review.';
$_['text_all_customer_groups']   = 'All customer groups';
$_['text_message_delay']         = 'Message delay:';
$_['text_message_delay_help']    = 'Define after how many days to send the email.<br /><br /><strong>NOTE: </strong>If you set the delay to 0, the message will be sent immediately after you change the order status.</span>';
$_['text_select_orders_by']      = 'Select orders by:';
$_['text_select_orders_by_help'] = 'Choose how the orders should be selected.';
$_['text_date_added']            = 'Date added';
$_['text_date_modified']         = 'Date modified';
$_['text_review_type']           = 'Review type:';
$_['text_review_type_help']      = 'Choose whether there should be one form for all products in a purchase or each product in the given purchase should have individual form.';
$_['text_per_product']           = 'Per Product';
$_['text_per_purchase']          = 'Per Purchase';
$_['text_dispaly_images']        = 'Display images:';
$_['text_dispaly_images_help']   = 'Choose whether to display product images in the email which will be sent to the customer.';
$_['text_mail_review_subject']   = 'MailReview Subject';
$_['text_email_preview']         = 'Email Preview';
$_['text_message']               = 'Message:';
$_['text_review_mail_settings']  = 'Review Mail Settings:';
$_['text_review_mail_subject']   ='Subject:';
$_['text_email_shortcodes']      = 'You can use the following short-codes:
                  <br />
                  <br />{first_name} - First name
                  <br />{last_name} - Last name
                  <br />{order_products} - Ordered products
                  <br />{review_form} - Review form
                  <br />{order_id} - Order ID (optional)
                  <br />{reviewmail_link} - Link for online form of the email';
$_['text_email_default_message'] = '
<table style="width:100%;font-family:Verdana;">
  	<tbody>
        <tr>
              <td align="center">
                 <table style="width:680px;margin:0 auto;border:1px solid #f0f0f0;line-height:1.8;font-size:1em;font-family:Verdana;">
                       <tbody>
                             <tr>
                                   <td style="font-family:inherit;padding:10px;">
                                      {reviewmail_link}
                       
                                      <p><span style="font-family: inherit; font-size: 1em; line-height: 1.8;">​Hello {first_name} {last_name},</span></p>
                       
                                      <p>Recently you bought {order_products} from our store. What do you think about the product(s) you ordered?</p>
                       
                                      <p>{review_form}</p>
                       
                                      <p>We really appreciate your feedback and we hope that you will visit us again soon.</p>
                       
                                      <p>Kind Regards,<br />
                                      OrderReviews</p>
                                      <p><a href="{catalog_link}" target="_blank"></a></p>
                                   </td>
                             </tr>
                       </tbody>
                 </table>
              </td>
        </tr>
  </tbody>
</table>';
$_['text_discount_type']             = 'Type of discount:';
$_['text_discount_type_help']        = 'If you choose the option \'No discount\', you will have to remove the following codes from the mail template: {discount_code}, {discount_value}, {total_amount} and {date_end}.';
$_['text_discount']                  = 'Discount:';
$_['text_discount_help']             = 'Enter the discount percent or value.';
$_['text_total_amount']              = 'Total amount:';
$_['text_total_amount_help']         = 'The total amount that must reached before the coupon is valid.';
$_['text_validity']                  = 'Discount validity:';
$_['text_validity_help']             = 'Define how many days the discount code will be active after sending the reminder.';
$_['text_discount_mail_option']      = 'Discount mail status:';
$_['text_discount_mail_option_help'] = 'The customer will receive information about his discount after he submits a review directly in the success page. If you enable this option, the customer will also receive an email with the discount information.';
$_['text_discount_mail_settings']    = 'Discount Mail Settings:';
$_['text_discount_mail_subject']     = 'Discount Mail Subject';
$_['text_discount_mail_preview']     = 'Discount Mail Preview';

$_['text_discount_mail_help']        = 'Use can use the following short-codes:
                            <br />
                            <br />{first_name} - First name
                            <br />{last_name} - Last name
                            <br />{discount_code} - Discount code
                            <br />{discount_value} - Discount value
                            <br />{total_amount} - Total amout
                            <br />{product_discount} - Products List
                            <br />{category_discount} - Categories List
                            <br />{date_end} - Date end
                            <br />{order_id} - Order ID
                            <br /><br />';

$_['text_discount_mail_default_message'] = '
<table style="font-family:verdana; width:100%">
  <tbody>
      <tr>
          <td>
              <table style="border:1px solid #f0f0f0; font-family:verdana; font-size:1em; line-height:1.8; margin:0 auto; width:680px">
                  <tbody>
                      <tr>
                          <td style="padding:10px;">
                              <p>Hello {first_name} {last_name},<br />
                                  <br />
                              Thank you for your review!</p>
                              
                              <p>We would like to give you a special discount code - <strong>{discount_code}</strong> - which gives you <strong>{discount_value} OFF</strong>.&nbsp;The code applies after you spent <strong>{total_amount}</strong>. This promotion is just for you and expires on <strong>{date_end}</strong>.</p>
                              
                              <p>You can apply the discount for all products and or all products under categories below:</p>
                              <p>{product_discount}</p>
							  <br />
                              <p>{category_discount}</p>
                              <br />
                              <p>We hope that you will visit us again soon.</p>
                              
                              <p>Kind Regards,<br />
                              OrderReviews.</p>
                              
                              <p><a href="{catalog_url}" target="_blank"></a></p>
                          </td>
                      </tr>
                  </tbody>
              </table>
          </td>
      </tr>
  </tbody>
</table>';
$_['text_select_date_format']         = 'Select date format for the end date of coupon validity:';

$_['entry_category']			= 'Category';
$_['entry_product']				= 'Products';
$_['help_category']				= 'Choose all products under selected category.';
$_['help_product']				= 'Choose specific products the coupon will apply to. Select no products to apply coupon to entire cart.';
$_['text_logs']					= 'Keep Log';
$_['text_log_help']				= 'Keep log of the sent emails.';
$_['text_order_id']				= 'Order ID';
$_['text_customer']				= 'Customer';
$_['text_email']				= 'Email';
$_['text_date']					= 'Date';
$_['text_remove']				= 'Remove';
$_['text_auto_approve']			= 'Auto Approve';
$_['text_auto_approve_help']	= 'Use this feature if you want to auto-approved your customer reviews.';
$_['text_auto_approve_setting']	= 'Auto Approve Stars Rating';
$_['text_auto_approve_setting_help']	= 'Choose minimum stars rating that will be automatically approved';


//Main tabs
$_['text_control_panel']         = 'Control Panel';
$_['text_received_reviews']      = 'Received Reviews';
$_['text_sent_coupons']          = 'Sent Coupons';
$_['text_mails_log']			 = 'Sent Emails Log';
$_['text_tab_support']           = 'Support';

// Review tabs
$_['text_general']               = 'General';
$_['text_configuration']         = 'Configuration';
$_['text_email_template']        = 'Email Template';
$_['text_discount_settings']     = 'Discount Settings';
$_['text_discount_email']        = 'Discount Email Template';

// Licensing
$_['text_your_license']          = 'Your License';
$_['text_please_enter_the_code'] = 'Please enter your product purchase license code:';
$_['text_activate_license']      = 'Activate License';
$_['text_not_having_a_license']  = 'Not having a code? Get it from here.';
$_['text_license_holder']        = 'License Holder';
$_['text_registered_domains']    = 'Registered domains';
$_['text_expires_on']            = 'License Expires on';
$_['text_valid_license']         = 'VALID LICENSE';
$_['text_manage']                = 'manage';
$_['text_get_support']           = 'Get Support';
$_['text_community']             = 'Community';
$_['text_ask_our_community']     = 'We have a big community. You are free to ask it about your issue on the forum.';
$_['text_browse_forums']         = 'Browse forums';
$_['text_tickets']               = 'Tickets';
$_['text_open_a_ticket']         = 'Want to communicate one-to-one with our tech people? Then open a support ticket.';
$_['text_open_ticket_for_real']  = 'Open a ticket';
$_['text_pre_sale']              = 'Pre-sale';
$_['text_pre_sale_text']         = 'Have a brilliant idea for your webstore? Our team of developers can make it real.';
$_['text_bump_the_sales']        = 'Bump the sales';

$_['text_privacy_policy']           = "Privacy Policy";
$_['text_privacy_policy_help']      = "Use this feature if you want your customers to accept the Privacy Policy. Compatible with GDPR Compliance.";
$_['text_agree']                    = "Agree";