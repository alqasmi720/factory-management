<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class FactoryWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_create_customer_order_and_initial_payment_with_allocation(): void
    {
        $this->actingAs(User::factory()->create(['role'=>'Administrator / Owner']));
        $this->post('/parties/customer',['name'=>'Crescent Cartons'])->assertRedirect();
        $customer=DB::table('customers')->first();
        $this->post('/orders',[
            'customer_id'=>$customer->id,'initial_payment'=>'25.00','payment_method'=>'Cash',
            'items'=>[
                ['product_type'=>'Shipping carton','paper_grade'=>'Kraft','length'=>'300','width'=>'200','height'=>'150','unit'=>'mm','quantity'=>'2','unit_price'=>'50.00'],
                ['product_type'=>'Divider','paper_grade'=>'Kraft','length'=>'100','width'=>'100','height'=>'20','unit'=>'mm','quantity'=>'5','unit_price'=>'10.00'],
            ],
        ])->assertRedirect();
        $order=DB::table('orders')->first();
        $this->assertSame(0,bccomp('150.00',(string)$order->total,2));
        $this->assertSame(0,bccomp('25.00',(string)DB::table('customer_payment_allocations')->sum('amount'),2));
        $this->assertSame('CR-'.now()->year.'-000001',DB::table('customer_payments')->value('number'));
        $this->get('/documents/orders/'.$order->id.'/invoice')->assertOk()->assertSee('Crescent Cartons');
        $invoicePdf=$this->get('/documents/orders/'.$order->id.'/invoice.pdf')->assertOk()->assertHeader('content-type','application/pdf');
        $this->assertStringStartsWith('%PDF',$invoicePdf->getContent());
        $this->get('/documents/customers/'.$customer->id.'/statement')->assertOk()->assertSee('Opening balance');
        $statementPdf=$this->get('/documents/customers/'.$customer->id.'/statement.pdf')->assertOk()->assertHeader('content-type','application/pdf');
        $this->assertStringStartsWith('%PDF',$statementPdf->getContent());
        $paymentId=DB::table('customer_payments')->value('id');
        $this->get('/documents/payments/customer/'.$paymentId.'/receipt')->assertOk()->assertSee('RECEIPT');
        $receiptPdf=$this->get('/documents/payments/customer/'.$paymentId.'/receipt.pdf')->assertOk()->assertHeader('content-type','application/pdf');
        $this->assertStringStartsWith('%PDF',$receiptPdf->getContent());
        $this->get('/?page_name=edit-order&id='.$order->id)->assertOk()->assertSee('Edit order');
        $this->post('/orders/'.$order->id.'/edit',[
            'customer_id'=>$customer->id,'items'=>[['product_type'=>'Revised carton','paper_grade'=>'Kraft','unit'=>'mm','quantity'=>2,'unit_price'=>'100.00']],
        ])->assertRedirect('/?page_name=order&id='.$order->id);
        $this->assertSame(0,bccomp('200.00',(string)DB::table('orders')->where('id',$order->id)->value('total'),2));
    }

    public function test_operational_staff_cannot_record_financial_transactions(): void
    {
        $this->actingAs(User::factory()->create(['role'=>'Operational Staff']));
        $this->post('/payments/customer',['party_id'=>1,'amount'=>10,'method'=>'Cash','paid_at'=>now()])->assertForbidden();
    }

    public function test_guest_is_sent_to_staff_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_one_payment_can_be_allocated_across_two_orders(): void
    {
        $user=User::factory()->create(['role'=>'Accounts Staff']);$this->actingAs($user);
        $customer=DB::table('customers')->insertGetId(['code'=>'CUS-0001','name'=>'Split Allocation Customer','active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $orders=[];foreach([100,150] as $i=>$total)$orders[]=DB::table('orders')->insertGetId(['number'=>'ORD-'.now()->year.'-'.str_pad((string)($i+1),6,'0',STR_PAD_LEFT),'customer_id'=>$customer,'ordered_at'=>now(),'status'=>'Pending','delivery_status'=>'Pending','total'=>$total,'created_at'=>now(),'updated_at'=>now()]);
        $payment=DB::table('customer_payments')->insertGetId(['number'=>'CR-'.now()->year.'-000001','customer_id'=>$customer,'paid_at'=>now(),'amount'=>125,'method'=>'Bank transfer','recorded_by'=>$user->id,'created_at'=>now(),'updated_at'=>now()]);
        $this->post('/payments/customer/'.$payment.'/allocations',['allocations'=>[['document_id'=>$orders[0],'amount'=>'75.00'],['document_id'=>$orders[1],'amount'=>'50.00']]])->assertRedirect();
        $this->assertSame(2,DB::table('customer_payment_allocations')->where('customer_payment_id',$payment)->count());
        $this->assertSame(0,bccomp('125.00',(string)DB::table('customer_payment_allocations')->where('customer_payment_id',$payment)->sum('amount'),2));
    }

    public function test_owner_can_preview_and_commit_opening_balance_csv(): void
    {
        $this->actingAs(User::factory()->create(['role'=>'Administrator / Owner']));
        $file=UploadedFile::fake()->createWithContent('customers.csv',"name,business_name,phone,address,notes,opening_balance\nPaper Town,Paper Town,03001234567,Lahore,Opening import,450.75\n");
        $this->post('/imports/customer/preview',['file'=>$file])->assertRedirect();
        $this->assertSame(1,count(session('factory_import_customer')['rows']));
        $this->post('/imports/customer/commit')->assertRedirect('/?page_name=customers');
        $this->assertSame('450.75',(string)DB::table('customers')->where('name','Paper Town')->value('opening_balance'));
        $this->assertDatabaseHas('audit_logs',['action'=>'imported','record_type'=>'customer']);
    }
}
