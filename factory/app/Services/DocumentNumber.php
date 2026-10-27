<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class DocumentNumber
{
    public function next(string $series,string $prefix): string
    {
        $year=(int)now()->year; DB::table('document_sequences')->insertOrIgnore(['series'=>$series,'year'=>$year,'last_value'=>0,'created_at'=>now(),'updated_at'=>now()]);
        $row=DB::table('document_sequences')->where('series',$series)->where('year',$year)->lockForUpdate()->first(); $next=$row->last_value+1;
        DB::table('document_sequences')->where('id',$row->id)->update(['last_value'=>$next,'updated_at'=>now()]); return sprintf('%s-%d-%06d',$prefix,$year,$next);
    }
}
