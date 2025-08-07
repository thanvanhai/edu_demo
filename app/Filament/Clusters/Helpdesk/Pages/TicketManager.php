<?php

namespace App\Filament\Clusters\Helpdesk\Pages;

use App\Filament\Clusters\Helpdesk;
use Illuminate\Contracts\View\View;
use Filament\Pages\Page;
use Filament\Pages\SubNavigationPosition;
use Filament\Support\Enums\MaxWidth;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use App\Models\Helpdesk\{Ticket, TicketCategory};
use Illuminate\Support\Facades\Auth;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Forms\Components\{Select,  TextInput, Textarea, Wizard, Wizard\Step, FileUpload};
use Filament\Forms\Components\Actions\Action;
use Illuminate\Support\Str;

class TicketManager extends Page implements HasForms
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
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

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Wizard::make([
                    Step::make('Loại sự cố')
                        ->description('Chọn một loại')
                        ->schema([
                            Select::make('ticket_category_id')
                                ->label('Loại sự cố')
                                ->options(TicketCategory::pluck('name', 'id'))
                                ->required()
                                ->live() // cần để trigger thay đổi
                                ->afterStateUpdated(function ($state, callable $set) {
                                    $set('code', 'TIC-' . strtoupper(Str::random(6)));
                                }),
                            TextInput::make('code')
                                ->label('Mã phiếu')
                                ->disabled()
                                ->dehydrated(true) // vẫn lưu vào $form->getState()
                                ->required(),
                        ])
                        ->columns(2),

                    Step::make('Chi tiết')
                        ->description('Nhập mô tả sự cố')
                        ->schema([
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
                                ->directory('tickets/attachments') // thư mục trong storage/app/public
                                ->visibility('public'),
                        ]),
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
        $data = $this->form->getState();

        $attachmentPaths = $data['attachments'] ?? [];
        // Ghép tất cả path thành chuỗi: "path1|path2|path3"
        $attachmentPathString = implode('|', $attachmentPaths);

        $ticket = Ticket::create([
            'code' => $data['code'], // ✅ Mã phiếu đã sinh ở bước 1
            'category_id' => $data['ticket_category_id'],
            'description' => $data['description'],
            'attachment_path' => $attachmentPathString, // ✅ lưu nhiều path gộp
            'user_id' => Auth::id(),
        ]);

        // Nếu có đính kèm file, lưu riêng (tuỳ cấu trúc DB)
        if (!empty($data['attachments'])) {
            foreach ($data['attachments'] as $path) {
                $ticket->attachments()->create([
                    'file_path' => $path,
                    'type' => 'create', // ✅ đánh dấu đây là file đính kèm khi tạo phiếu
                ]);
            }
        }
        Notification::make()
            ->title('Gửi phiếu sự cố thành công')
            ->success()
            ->send();

        $this->redirect('/admin');
    }
}
