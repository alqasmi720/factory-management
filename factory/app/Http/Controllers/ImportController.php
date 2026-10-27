<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ImportController extends Controller
{
    public function preview(Request $request,string $type)
    {
        $this->owner($type); $request->validate(['file'=>'required|file|mimes:csv,txt|max:5120']);
        $handle=fopen($request->file('file')->getRealPath(),'r'); $headers=fgetcsv($handle); $expected=['name','business_name','phone','address','notes','opening_balance'];
        abort_unless($headers && array_intersect($expected,array_map(fn($x)=>strtolower(trim($x)),$headers)) && in_array('name',array_map(fn($x)=>strtolower(trim($x)),$headers)),422,'CSV must contain a name column.');
        $headers=array_map(fn($x)=>strtolower(trim($x)),$headers); $rows=[];$errors=[];$line=1;
        while(($values=fgetcsv($handle))!==false){$line++;if(count(array_filter($values,fn($v)=>trim((string)$v)!==''))===0)continue;if(count($values)!==count($headers)){$errors[]="Row $line: number of columns does not match the header.";continue;}$row=array_combine($headers,$values);$row=array_intersect_key($row,array_flip($expected));$row=array_map(fn($v)=>trim((string)$v),$row);if($row['name']===''){$errors[]="Row $line: name is required.";continue;}if(isset($row['opening_balance'])&&(!is_numeric($row['opening_balance'])||!preg_match('/^\d+(\.\d{1,2})?$/',$row['opening_balance']))){$errors[]="Row $line: opening_balance must be a non-negative amount with at most 2 decimals.";continue;}$table=$type.'s';$duplicate=DB::table($table)->where('name',$row['name'])->when($row['phone']??'',fn($q,$p)=>$q->where('phone',$p))->exists();$rows[]=['line'=>$line,'data'=>$row,'duplicate'=>$duplicate];}
        fclose($handle);$request->session()->put('factory_import_'.$type,['rows'=>$rows,'errors'=>$errors,'created_by'=>$request->user()->id]);return redirect('/?page_name=imports&type='.$type)->with('success','CSV preview is ready. Review duplicates and row errors before commit.');
    }

    public function commit(Request $request,string $type,AuditLogger $audit)
    {
        $this->owner($type);$preview=$request->session()->get('factory_import_'.$type);abort_unless($preview && $preview['created_by']===$request->user()->id,422,'No import preview is available. Upload the CSV again.');abort_if(count($preview['errors'])>0,422,'Correct CSV errors and upload again before committing.');$table=$type.'s';$inserted=0;$skipped=0;
        DB::transaction(function()use($preview,$table,$type,&$inserted,&$skipped,$audit){foreach($preview['rows'] as $entry){if($entry['duplicate']){$skipped++;continue;}$r=$entry['data'];$next=(int)DB::table($table)->lockForUpdate()->max('id')+1;$id=DB::table($table)->insertGetId(['code'=>strtoupper(substr($type,0,3)).'-'.str_pad((string)$next,6,'0',STR_PAD_LEFT),'name'=>$r['name'],'business_name'=>$r['business_name']??null,'phone'=>$r['phone']??null,'address'=>$r['address']??null,'notes'=>$r['notes']??null,'opening_balance'=>$r['opening_balance']??0,'active'=>true,'created_at'=>now(),'updated_at'=>now()]);$audit->record('imported',$type,$id,null,['opening_balance'=>$r['opening_balance']??0,'source_row'=>$entry['line']]);$inserted++;}});
        $request->session()->forget('factory_import_'.$type);return redirect('/?page_name='.($type==='customer'?'customers':'suppliers'))->with('success',"Import complete: $inserted created, $skipped duplicates skipped.");
    }
    private function owner(string $type): void { abort_unless(in_array($type,['customer','supplier']),404); abort_unless(auth()->user()?->role==='Administrator / Owner',403); }
}
