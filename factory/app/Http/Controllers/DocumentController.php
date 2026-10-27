<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class DocumentController extends Controller
{
    public function invoice(int $id)
    {
        $order=DB::table('orders')->join('customers','customers.id','=','orders.customer_id')->select('orders.*','customers.name as customer','customers.business_name','customers.phone','customers.address','customers.code')->where('orders.id',$id)->first() ?? abort(404);
        $items=DB::table('order_items')->where('order_id',$id)->get();
        $payments=DB::table('customer_payments')->join('customer_payment_allocations','customer_payment_allocations.customer_payment_id','=','customer_payments.id')->where('customer_payment_allocations.order_id',$id)->whereNull('customer_payments.voided_at')->select('customer_payments.*','customer_payment_allocations.amount as allocated_amount')->orderBy('paid_at')->get();
        $paid=$payments->sum('allocated_amount');
        return view('documents.invoice',compact('order','items','payments','paid'));
    }

    public function invoicePdf(int $id)
    {
        $view=$this->invoice($id); return Pdf::loadView('documents.invoice',$view->getData())->setPaper('a4')->download('invoice-'.$id.'.pdf');
    }

    public function customerStatement(int $id, Request $request)
    {
        $customer=DB::table('customers')->where('id',$id)->first() ?? abort(404);
        $from=$request->date('from')?->startOfDay(); $to=$request->date('to')?->endOfDay();
        $charges=DB::table('orders')->where('customer_id',$id)->whereNotIn('status',['Cancelled'])->get()->map(fn($r)=>(object)['date'=>$r->ordered_at,'reference'=>$r->number,'description'=>'Order charge','debit'=>(string)$r->total,'credit'=>'0.00']);
        $payments=DB::table('customer_payments')->where('customer_id',$id)->whereNull('voided_at')->get()->map(fn($r)=>(object)['date'=>$r->paid_at,'reference'=>$r->number,'description'=>'Payment · '.$r->method,'debit'=>'0.00','credit'=>(string)$r->amount]);
        [$entries,$opening]=$this->statementPeriod($charges->concat($payments),$customer->opening_balance,$from,$to);
        return view('documents.statement', ['party'=>$customer,'type'=>'Customer statement','entries'=>$entries,'opening'=>$opening,'from'=>$from,'to'=>$to]);
    }

    public function customerStatementPdf(int $id,Request $request)
    {
        $view=$this->customerStatement($id,$request); return Pdf::loadView('documents.statement',$view->getData())->setPaper('a4')->download('customer-statement-'.$id.'.pdf');
    }

    public function supplierStatement(int $id, Request $request)
    {
        $supplier=DB::table('suppliers')->where('id',$id)->first() ?? abort(404);
        $from=$request->date('from')?->startOfDay(); $to=$request->date('to')?->endOfDay();
        $charges=DB::table('purchases')->where('supplier_id',$id)->get()->map(fn($r)=>(object)['date'=>$r->purchased_at,'reference'=>$r->number,'description'=>$r->description,'debit'=>(string)$r->total,'credit'=>'0.00']);
        $payments=DB::table('supplier_payments')->where('supplier_id',$id)->whereNull('voided_at')->get()->map(fn($r)=>(object)['date'=>$r->paid_at,'reference'=>$r->number,'description'=>'Payment · '.$r->method,'debit'=>'0.00','credit'=>(string)$r->amount]);
        [$entries,$opening]=$this->statementPeriod($charges->concat($payments),$supplier->opening_balance,$from,$to);
        return view('documents.statement', ['party'=>$supplier,'type'=>'Supplier statement','entries'=>$entries,'opening'=>$opening,'from'=>$from,'to'=>$to]);
    }

    public function supplierStatementPdf(int $id,Request $request)
    {
        $view=$this->supplierStatement($id,$request); return Pdf::loadView('documents.statement',$view->getData())->setPaper('a4')->download('supplier-statement-'.$id.'.pdf');
    }

    public function receipt(string $type, int $paymentId)
    {
        abort_unless(in_array($type,['customer','supplier']),404); $table=$type.'_payments';
        $payment=DB::table($table)->join($type==='customer'?'customers':'suppliers',$type.'s.id','=',$table.'.'.$type.'_id')->where($table.'.id',$paymentId)->whereNull($table.'.voided_at')->select($table.'.*',$type.'s.name as party',$type.'s.code as party_code',$type.'s.phone')->first() ?? abort(404);
        $allocations=$type==='customer'?DB::table('customer_payment_allocations')->join('orders','orders.id','=','customer_payment_allocations.order_id')->where('customer_payment_id',$paymentId)->select('orders.number as reference','customer_payment_allocations.amount')->get():DB::table('supplier_payment_allocations')->join('purchases','purchases.id','=','supplier_payment_allocations.purchase_id')->where('supplier_payment_id',$paymentId)->select('purchases.number as reference','supplier_payment_allocations.amount')->get();
        return view('documents.receipt',compact('payment','allocations','type'));
    }

    public function receiptPdf(string $type,int $paymentId)
    {
        $view=$this->receipt($type,$paymentId); return Pdf::loadView('documents.receipt',$view->getData())->setPaper('a4')->download('receipt-'.$view->getData()['payment']->number.'.pdf');
    }

    private function statementPeriod($entries,string|float|null $opening, mixed $from,mixed $to): array
    {
        $balance=(string)($opening??'0.00');$period=collect();
        foreach($entries->sortBy('date') as $entry){$date=\Carbon\Carbon::parse($entry->date);if($from && $date->lt($from)){$balance=bcadd($balance,bcsub((string)$entry->debit,(string)$entry->credit,2),2);continue;}if($to && $date->gt($to))continue;$period->push($entry);}
        return [$period,$balance];
    }
}
