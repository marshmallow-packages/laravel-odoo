<?php

declare(strict_types=1);

namespace Marshmallow\Odoo\Resources;

use Marshmallow\Odoo\Support\Domain;

class Invoices extends Resource
{
    protected string $model = 'account.move';

    /**
     * Confirm draft invoice(s): action_post. A separate transaction from
     * create(), so store the id first and treat a failure here as retryable.
     *
     * @param  int|array<int, int>  $ids
     */
    public function post(int|array $ids): void
    {
        $this->call('action_post', [], (array) $ids);
    }

    /**
     * The payment_state of an invoice: not_paid, in_payment, paid, partial, reversed, invoicing_legacy.
     */
    public function paymentState(int $id): string
    {
        return (string) ($this->find($id, ['payment_state'])['payment_state'] ?? '');
    }

    /**
     * A domain limited to customer invoices.
     */
    public static function customerInvoices(): Domain
    {
        return Domain::make()->where('move_type', 'out_invoice');
    }

    /**
     * A domain limited to customer credit notes.
     */
    public static function creditNotes(): Domain
    {
        return Domain::make()->where('move_type', 'out_refund');
    }
}
