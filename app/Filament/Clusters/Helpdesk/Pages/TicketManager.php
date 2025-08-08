<?php

namespace App\Filament\Clusters\Helpdesk\Pages;

use App\Filament\Clusters\Helpdesk;
use Illuminate\Contracts\View\View;
use Filament\Pages\Page;
use Filament\Pages\SubNavigationPosition;
use Filament\Support\Enums\MaxWidth;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use App\Models\Helpdesk\{Ticket, TicketCategory, TicketVehicleCard};
use App\Models\NhanSu\Department;
use Illuminate\Support\Facades\Auth;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Forms\Components\{Select,  TextInput, Textarea, Wizard, Wizard\Step, FileUpload};
use Filament\Forms\Components\Actions\Action;
use Illuminate\Support\Str;

class TicketManager extends Page implements HasForms
{
    protected static ?string $navigationIcon = 'heroicon-o-newspaper';
    protected static string $view = 'filament.clusters.helpdesk.pages.ticket-manager';
    protected static ?string $cluster = Helpdesk::class;
    protected static ?string $title = 'Phiếu hỗ trợ';
    protected static ?string $navigationLabel = 'Phiếu hỗ trợ';
    protected static ?string $slug = 'ticket-manager';
    protected static ?int $navigationSort = 1;
    protected static bool $shouldRegisterNavigation = true;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top; // Or SubNavigationPosition::Start

    use InteractsWithForms;

    public array $data = [];

    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }

    public function mount(): void
    {
        $this->form->fill(); // Khởi tạo form nếu cần
    }
    protected function isVehicleCardCategory($ticketCategoryId)
    {
        return TicketCategory::where('id', $ticketCategoryId)
            ->where('name', 'Đăng ký thẻ xe')
            ->exists();
    }
    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Wizard::make([
                    Step::make('Loại hỗ trợ')
                        ->description('Chọn một loại')
                        ->schema([
                            Select::make('ticket_category_id')
                                ->label('Loại sự cố')
                                ->options(TicketCategory::pluck('name', 'id'))
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    $set('code', 'TIC-' . strtoupper(Str::random(6)));
                                }),
                            TextInput::make('code')
                                ->label('Mã phiếu')
                                ->disabled()
                                ->dehydrated(true)
                                ->required(),

                            Select::make('department_id')
                                ->label('Đơn vị')
                                ->options(Department::pluck('name', 'id'))
                                ->searchable()
                                ->required(),
                        ])
                        ->columns(2),

                    Step::make('Chi tiết cần hỗ trợ')
                        ->description('Nhập thông tin')
                        ->schema(function (callable $get) {
                            // Nếu loại sự cố là "Đăng ký thẻ xe"
                            if ($this->isVehicleCardCategory($get('ticket_category_id'))) {
                                return [
                                    TextInput::make('full_name')
                                        ->label('Họ và tên')
                                        ->required(),

                                    TextInput::make('employee_code')
                                        ->label('Mã nhân sự')
                                        ->required(),

                                    TextInput::make('email')
                                        ->label('Email')
                                        ->email()
                                        ->required(),

                                    TextInput::make('phone')
                                        ->label('Số điện thoại')
                                        ->tel()
                                        ->required(),

                                    TextInput::make('vehicle_type')
                                        ->label('Loại xe (AB, SH, Sirus, ô tô, xe đạp, ...)')
                                        ->placeholder('AB, SH, Sirus, ô tô, xe đạp, ...')
                                        ->required(),

                                    TextInput::make('vehicle_color')
                                        ->label('Màu xe')
                                        ->required(),

                                    TextInput::make('plate_number')
                                        ->label('Biển số xe Điền theo cú pháp sau: 60-XX-XXXXX Vd: 60-B9-34567')
                                        ->required(),

                                    FileUpload::make('id_card_image')
                                        ->label('Ảnh CCCD/CMND')
                                        ->image()
                                        ->directory('tickets/vehicle_cards')
                                        ->visibility('public'),

                                    FileUpload::make('vehicle_image')
                                        ->label('Ảnh xe')
                                        ->image()
                                        ->directory('tickets/vehicle_cards')
                                        ->visibility('public'),

                                    Textarea::make('note')
                                        ->label('Ghi chú')
                                        ->rows(3),
                                ];
                            }

                            // Trường mặc định cho loại khác
                            return [
                                Textarea::make('description')
                                    ->label('Mô tả chi tiết')
                                    ->rows(6)
                                    ->required(),
                                FileUpload::make('attachments')
                                    ->label('Đính kèm hình ảnh')
                                    ->multiple()
                                    ->image()
                                    ->imagePreviewHeight('150')
                                    ->maxSize(2048)
                                    ->directory('tickets/attachments')
                                    ->visibility('public'),
                            ];
                        }),
                ])
                    ->submitAction(
                        Action::make('submit')
                            ->label('Gửi phiếu')
                            ->action('submit')
                            ->color('primary')
                    ),
            ]);
    }

    public function submit(): void
    {
        // $data = $this->form->getState()['data'];
        $data = $this->data; // ✅ đúng nếu có public ?array $data
        // Nếu có ảnh đính kèm (cho loại sự cố bình thường)
        $attachmentPaths = $data['attachments'] ?? [];
        $attachmentPathString = implode('|', $attachmentPaths);

        $category = TicketCategory::find($data['ticket_category_id']);

        // Tạo Ticket trước
        $ticket = Ticket::create([
            'code' => $data['code'], // Mã phiếu từ step 1
            'category_id' => $data['ticket_category_id'],
            'department_id' => $data['department_id'],
            'title' => $category?->name ?? 'Phiếu hỗ trợ',
            'description' => $this->isVehicleCardCategory($data['ticket_category_id'])
                ? $category?->description
                : ($data['description'] ?? null),
            'attachment_path' => $attachmentPathString,
            'user_id' => Auth::id(),
        ]);

        // Lưu file đính kèm (nếu có) cho loại sự cố khác
        if (!empty($data['attachments'])) {
            foreach ($data['attachments'] as $path) {
                $ticket->attachments()->create([
                    'file_path' => $path,
                    'type' => 'create',
                ]);
            }
        }

        // Nếu là loại sự cố "Đăng ký thẻ xe" thì lưu vào bảng ticket_vehicle_cards
        if ($this->isVehicleCardCategory($data['ticket_category_id'])) {
            TicketVehicleCard::create([
                'ticket_id'     => $ticket->id,
                'employee_code' => $data['employee_code'] ?? null,
                'full_name'     => $data['full_name'] ?? null,
                'phone'         => $data['phone'] ?? null,
                'vehicle_type'  => $data['vehicle_type'] ?? null,
                'plate_number'  => $data['plate_number'] ?? null,
                'note'          => $data['note'] ?? null,
                'id_card_image' => $data['id_card_image'] ?? null,
                'vehicle_image' => $data['vehicle_image'] ?? null,
                'email'         => $data['email'] ?? null,
                'vehicle_color' => $data['vehicle_color'] ?? null
            ]);
        }

        Notification::make()
            ->title('Gửi phiếu sự cố thành công')
            ->success()
            ->send();

        $this->redirect('/admin');
    }
}
