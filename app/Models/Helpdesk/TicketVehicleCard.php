<?php
namespace App\Models\Helpdesk;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketVehicleCard extends Model
{
    use HasFactory;

    protected $table = 'ticket_vehicle_cards';

    protected $fillable = [
        'ticket_id',
        'full_name',
        'employee_code',   // Mã nhân sự
        'email',           // Email
        'phone',
        'vehicle_type',
        'vehicle_color',   // Màu xe
        'plate_number',
        'note',
        'id_card_image',
        'vehicle_image',
    ];

    // Quan hệ với Ticket
    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}
