<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use DB;

class Importacion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:importacion';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info("INICIANDO EL MODULO DE IMPORTACION");
        $path = storage_path('excel/import/empleados_dos.xlsx');
        $this->info($path);
        //Excel::selectSheets('sheet1')->load($path);
        Excel::selectSheetsByIndex(0)->load($path , function($reader) {
            $this->info("SE ENCONTRO EL ARCHIVO EXCEL");
            // reader methods
            $result = $reader->select(array('first_name','second_name','last_name','mother_last_name','city_identity_card_id','identity_card','position'))
            // ->take(100)
             ->get();
            foreach($result as $row){
                $empleado = DB::table('rrhh.employees')->where('identity_card',$row->identity_card)->first();
                if ($empleado) {
                    $this->info("EL EMPLEADO EXISTE EN LA BASE DE DATOS");                 
                }else{
                    $this->info($row);
                    $position = DB::table('rrhh.positions')->where('name','like','%'.$row->position.'%')->first();
                    if($position){
                        $this->info("EXISTE EL CARGO: ".$row->position.", BASE: ".$position->name);
                        DB::table('rrhh.employees')
                        ->insert([
                                  'first_name'=> trim($row->first_name),
                                  'second_name'=> trim($row->second_name),
                                  'last_name'=> trim($row->last_name),
                                  'mother_last_name'=> trim($row->mother_last_name),
                                  'city_identity_card_id' => trim($row->city_identity_card_id),
                                  'identity_card' => trim($row->identity_card),
                                  'position_id' => $position->id,
                                  ]);
                    }else{
                        $this->info("NO EXISTE EL CARGO: ".$row->position,', CREANDO CARGO NUEVO');
                        $cargo = DB::table('rrhh.positions')->insertGetId([
                            'name'  => $row->position,
                        ]);
                        $this->info($cargo);
                        DB::table('rrhh.employees')
                        ->insert([
                                  'first_name'=> trim($row->first_name),
                                  'second_name'=> trim($row->second_name),
                                  'last_name'=> trim($row->last_name),
                                  'mother_last_name'=> trim($row->mother_last_name),
                                  'city_identity_card_id' => trim($row->city_identity_card_id),
                                  'identity_card' => trim($row->identity_card),
                                  'position_id' => $cargo,
                                  ]);
                    }
                    
                }
                
            }
            $this->info('total rows:'.$result->count());
        });
    }
}
