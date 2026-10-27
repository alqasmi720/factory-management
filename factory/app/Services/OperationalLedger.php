<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/** Source-of-truth operational receivables/payables; this is not double-entry accounting. */
class OperationalLedger
{
    public function orderPaid(int $orderId): string
    {
        return (string) DB::table('customer_payment_allocations as a')->join('customer_payments as p','p.id','=','a.customer_payment_id')->where('a.order_id',$orderId)->whereNull('p.voided_at')->sum('a.amount');
    }

    public function purchasePaid(int $purchaseId): string
    {
        return (string) DB::table('supplier_payment_allocations as a')->join('supplier_payments as p','p.id','=','a.supplier_payment_id')->where('a.purchase_id',$purchaseId)->whereNull('p.voided_at')->sum('a.amount');
    }

    public function customerBalance(int $customerId): string
    {
        $opening=(string)DB::table('customers')->where('id',$customerId)->value('opening_balance');
        $charges=(string)DB::table('orders')->where('customer_id',$customerId)->whereNotIn('status',['Cancelled'])->sum('total');
        return bcsub(bcadd($opening ?: '0.00',$charges,2),$this->customerPayments($customerId),2);
    }

    public function supplierBalance(int $supplierId): string
    {
        $opening=(string)DB::table('suppliers')->where('id',$supplierId)->value('opening_balance');
        $charges=(string)DB::table('purchases')->where('supplier_id',$supplierId)->where('status','Open')->sum('total');
        return bcsub(bcadd($opening ?: '0.00',$charges,2),$this->supplierPayments($supplierId),2);
    }

    public function customerPayments(int $customerId): string
    {
        return (string)DB::table('customer_payments')->where('customer_id',$customerId)->whereNull('voided_at')->sum('amount');
    }

    public function supplierPayments(int $supplierId): string
    {
        return (string)DB::table('supplier_payments')->where('supplier_id',$supplierId)->whereNull('voided_at')->sum('amount');
    }
}
