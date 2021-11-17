<?php
// Heading
$_['heading_title']               = 'BirthdayReminder';
$_['birthday_text']               = 'Birthday:';

// Entry
$_['entry_code']                  = 'BirthdayReminder status:';
$_['entry_highlightcolor']        = 'Highlight color:<br /><span class="help">This is the color the keyword in the results highlights in.<br/><br/><em>Examples: red, blue, #F7FF8C</em></span>';
$_['error_permission']            = 'Warning: You do not have permission to modify module BirthdayReminder!';
$_['subject_text']                = 'Subject';
$_['select_coupon_text']          = 'Select an existing coupon: <span class="help">Select among existing OpenCart coupons.</span>';
$_['select_coupon']               = 'Select coupon';
$_['select_order_status_text']    = 'Select order status: <br /> <span class="help">Select order status that will generate an unique code for every customer</span>';
$_['select_order_status']         = 'Select order status';

$_['user_email']                  = 'Message to customer:';
$_['discount_code_text'] = 'Discount code';									
$_['default_message']             = '<p>Happy Birthday {firstname} {lastname},<br />
										<br />
										We want to help you celebrate your special date with a discount code <strong>{discount_code}</strong>. Enjoy a special {discount_value}% OFF. The discount code applies after you have spent ${total_amount}. The coupon expires on {date_end}.<br />
										<br />
										Happy birthday shopping!</p>
';
$_['default_subject']             = 'Happy birthday!';
$_['error_subject']               = 'Subject must be between 3 and 128 characters!';
$_['error_rule_name']             = 'Rule name must be between 3 and 128 characters!';
$_['error_template_not_selected'] = 'Template not selected!';
$_['error_event_not_selected']    = 'Order event not selected!';
$_['error_order_status']          = 'Order status not selected!';
$_['days_before_birthday']        = 'Days before birthday <span class="help">How many days before birthday to be sent wish</span>';
$_['total_amount']                = 'Total Amount:';
$_['discount_codeoup_text']          = 'Discount code: <span class="help">Unique code that will be added to database after sending mail</span>';
$_['admin_notification']          = 'Send BCC to store owner:';
$_['coupon_validity']             = ' Coupon validity:';
$_['select_custom_field']		  = 'Select custom field:';

//cron form
$_['cron_job_text']               = 'Send birthday wishes automatically: ';
$_['cron_select_admin_period'] 	= 'Email store owner';
$_['cron_select_customer_period'] = 'Email customer(s)';
$_['cron_customer_options_title'] = 'Customer notifications';
$_['cron_select_options_title']   = 'Select when to send gift mail to customer';
$_['cron_current_crons'] 		  =  'Current BirthdayReminder crons:'; 
$_['cron_week_day'] = 'Day: ';
$_['monday'] = 'Monday';
$_['tuesday'] = 'Tuesday';
$_['wednesday'] = 'Wednesday';
$_['thursday'] = 'Thursday';
$_['friday'] = 'Friday';
$_['saturday'] = 'Saturday';
$_['sunday'] = 'Sunday';
$_['cron_time'] = 'Time: ';
$_['cron_days_before'] = 'days before';
$_['cron_successfully_changed'] = ' Cron jobs successfuly modified! You can check cron jobs status by clicking on Cron jobs button.';
$_['birthday_calendar_info']  = 'The calendar displays all customer\'s upcoming birthdays. You can view the birthdays by year, month, week and day. In order to send a company greeting along with a birthday coupon code you need to click on the tab and select a customer.';
$_['message_has_been_sent'] = ' The message has been sent!';
$_['select_date_format'] = ' Select date format:';
$_['every_week']		= 'Every week';
$_['every_day']			= 'Every day';
$_['start_cron_button']	= 'Start cron jobs';
$_['clear_cron_button']	= 'Clear birthday cron jobs';
$_['the_day_of_birthday']= 'The day of birthday';
$_['before_birthday']	= 'Before birthday';
$_['type_of_discount']	= 'Type of discount:';
$_['percentage_text']	= 'Percentage';
$_['discount_text']		= 'Discount';
$_['fixed_amount']		= 'Fixed amount';
$_['send_gift_button']	= 'Send gift now'; 

// Text
$_['text_next']	=	'Next';
$_['text_prev']	=	'Prev';
$_['text_today']='Today'; 
$_['text_week']	=	'Week';
$_['text_day']	=	'Day';
$_['text_year']	= 'Year';
$_['text_month']	= 'Month';
$_['text_calendar']	= 'Calendar';
$_['text_days']		= 'days';
$_['text_subject']	= 'Subject';
$_['text_cron_jobs']= 'Cron jobs';
$_['text_save_change']= 'Save Changes';
$_['text_cancel']= 'Cancel';
$_['text_module']                 = 'Modules';
$_['text_success']                = ' Success: You have modified module BirthdayReminder!';
$_['text_yes']                    = 'Yes';
$_['text_no']                     = 'No';
$_['text_enabled']                = 'Enabled';
$_['text_disabled']               = 'Disabled';
$_['text_home'] = 'Home';

// Helpers
$_['help_list_current_birthdays'] = 'Use this option to configure how often to send a list of current birthdays to the store owner';
$_['help_when_send_emails_to_customers'] = 'Use this option to configure when to send emails to customer(s)';
$_['help_select_custom_field'] = 'Select custom field which will be used for entering birth date';
$_['help_coupon_expire_date'] = 'Select how many days the coupon to be valid after recieved';
$_['help_total_amount_before_use_counpon'] = 'The total amount that must reached before the coupon is valid.';
$_['help_message_with_discount'] = 'Message with discount code that will be sent to user.';
$_['help_user_email_template'] = 'Use the following codes:<br />
          {firstname} - fist name<br />
          {lastname} - last name<br />
          {discount_code} - the code of discount coupon<br />
          {total_amount} - total amount 
          {date_end} - end date of coupon validity';
$_['help_configure_cron_emails'] = 'Configure cron job to send emails automatically to customers and store owner.';
?>