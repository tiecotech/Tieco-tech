<?php

// Sales tracking, manual deals and owner email alerts (admin area).
return [
    // sale card on a request
    's.sale' => 'Deal & payments', 's.sold_at' => 'Sold on', 's.final_price' => 'Final agreed price', 's.received' => 'Received',
    's.remaining' => 'Expected (still due)', 's.estimate' => 'Website estimate', 's.quote' => 'Quote', 's.seller' => 'Closed by',
    's.not_sold' => 'Not marked as sold yet. When the customer agrees, record the sale date and final price.',
    's.mark_sold' => 'Record sale', 's.update_sale' => 'Save deal', 's.clear_sale' => 'Undo sale', 's.clear_confirm' => 'Remove the sale record? Payments stay saved.',
    's.paid_full' => 'Fully paid', 's.overpaid' => 'Received more than the final price',
    's.payments' => 'Payments received', 's.no_payments' => 'No payments recorded yet.',
    's.add_payment' => 'Add payment', 's.amount' => 'Amount', 's.paid_at' => 'Date', 's.method' => 'Method', 's.pay_note' => 'Note (optional)',
    's.delete' => 'Delete', 's.delete_confirm' => 'Delete this payment?',
    's.m.cash' => 'Cash', 's.m.transfer' => 'Bank transfer', 's.m.card' => 'Card', 's.m.other' => 'Other',
    's.src.website' => 'Website', 's.src.manual' => 'Manual',

    // manual deal form
    's.new_deal' => '+ Add deal manually', 's.new_title' => 'New deal (outside the website)',
    's.new_lead' => 'Archive a customer you sold to outside the website, so all your work lives in one place.',
    's.customer' => 'Customer', 's.name' => 'Customer name', 's.phone' => 'Phone', 's.email' => 'Email', 's.country' => 'Country',
    's.project' => 'Project', 's.type' => 'Project type', 's.type_none' => '— Other / custom —', 's.title' => 'Project title',
    's.title_hint' => 'e.g. Restaurant website', 's.description' => 'Details / notes', 's.status' => 'Status',
    's.deal' => 'Deal', 's.first_payment' => 'Payment received (optional)', 's.save' => 'Save deal',
    's.optional' => 'Leave empty if not sold yet.',

    // requests list
    's.f.all_deals' => 'All deals', 's.f.sold' => 'Sold', 's.f.unsold' => 'Not sold', 's.f.due' => 'Money still due',
    's.f.all_sources' => 'All sources', 's.col.deal' => 'Deal',

    // dashboard
    's.d.title' => 'Sales', 's.d.month' => 'This month', 's.d.deals' => 'Deals', 's.d.sold' => 'Sold', 's.d.received' => 'Received',
    's.d.expected' => 'Expected (due)', 's.d.all_time' => 'All time', 's.d.months' => 'Last 6 months', 's.d.due' => 'Money still due',
    's.d.no_due' => 'Nothing outstanding 🎉',

    // settings → notifications
    's.alert_title' => 'Email alerts', 's.alert_lead' => 'Get an email in your personal inbox whenever a new project request or contact message arrives.',
    's.alert_email' => 'Send alerts to', 's.alert_enabled' => 'Email alerts on', 's.alert_test' => 'Send test email', 's.alert_sent' => 'Test email sent — check your inbox (and spam).',
    's.alert_mailer_off' => 'Email sending is not set up on the server yet (MAIL_MAILER=:mailer). Alerts are saved but no email goes out until SMTP is configured in .env.',
    's.alert_no_email' => 'Save an alert email address first.',
    's.alert_no_mailer' => 'Email sending is not configured on the server (set SMTP in .env).',
    's.alert_failed' => 'Sending failed:',

    // timeline notes written by the server
    's.note_manual' => 'Deal added manually (sold outside the website).',
    's.note_sold' => 'Sale recorded: :date — final price :amount',
    's.note_unsold' => 'Sale record removed.',
    's.note_paid' => 'Payment received: :amount on :date',
];
