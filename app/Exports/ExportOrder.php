<?php

namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;

ini_set('memory_limit','1024M');

class ExportOrder implements FromQuery,WithMapping,WithHeadings
{
    private int $userId;
    private ?string $startDate;
    private ?string $endDate;

    public function __construct(int $userId, ?string $startDate = null, ?string $endDate = null)
    {
        $this->userId = $userId;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    // public function collection()
    // {
    //     return Order::where('user_id',Auth::user()->id)->select('ref','subcategory_id','description','subtotal','phone','iuc','meter','bal','prev_bal','response','status','created_at')->get();
    // }
    
    public function query()
    {
        return Order::with([
                'category:id,title',
                'subcategory:id,category_id,title',
                'subcategory.category:id,title',
            ])
            ->where('user_id', $this->userId)
            ->when($this->startDate, function ($query) {
                $query->where('created_at', '>=', $this->startDate);
            })
            ->when($this->endDate, function ($query) {
                $query->where('created_at', '<=', $this->endDate);
            })
            ->orderBy('created_at', 'desc');
        
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
    
    public function map($order): array
    {
        \Log::info("FROM EXPORT ORDER MAP");
        \Log::info($order);
        
        return [
            $order->ref,
            optional($order->category)->title
                ?? optional(optional($order->subcategory)->category)->title
                ?? '-',
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
