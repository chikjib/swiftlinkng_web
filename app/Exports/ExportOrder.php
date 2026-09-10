<?php

namespace App\Exports;

use App\Models\Order;
use App\Models\User;
use App\Models\Subcategory;
use App\Models\Category;
use Illuminate\Support\Facades\Auth;


use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;

ini_set('memory_limit','1024M');

class ExportOrder implements FromQuery,WithMapping,WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    // public function collection()
    // {
    //     return Order::where('user_id',Auth::user()->id)->select('ref','subcategory_id','description','subtotal','phone','iuc','meter','bal','prev_bal','response','status','created_at')->get();
    // }
    
    public function query()
    {
        return Order::with(['subcategory:id,category_id,title'])->where('user_id',Auth::user()->id)->orderBy('created_at', 'desc');
        
    }
    
    
    private function getStatus($stat)
    {
        switch($stat)
        { 
            case "0":
                return "Pending"; 
                break;
            case "1":
                return "Confirmed";
                break;
            case "2":
                return "Reversed";
                break;
            case "3":
                return "Ignored";
                break;
            case "4":
                return "Failed";
                break;
            default:
                return "Failed";
                break;
        }
        
    }
    
    private function getType($id)
    {
        $category = Category::find($id);
        $title = $category->title;
        return $title;
    }
    
    public function map($order): array
    {
        \Log::info("FROM EXPORT ORDER MAP");
        \Log::info($order);
        
        return [
            $order->ref,
            $this->getType($order->subcategory->category_id),
            $order->description,
            $order->subtotal,
            $order->phone,
            $order->iuc,
            $order->meter,
            $order->bal,
            $order->prev_bal,
            $order->response,
            $this->getStatus($order->status),
            $order->created_at
        ];
    }
    
    public function headings(): array
    {
        return [
            'Ref',
            'Type',
            'Description',
            'Amount',
            'Phone',
            'Iuc',
            'Meter',
            'Bal',
            'Prev Bal',
            'Response',
            'Status',
            'Created At'
        ];
    }
}
