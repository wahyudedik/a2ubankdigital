<?php

namespace App\Http\Controllers\Inertia;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Account;
use App\Models\Loan;
use App\Models\LoanProduct;
use App\Models\DepositProduct;
use App\Models\CustomerProfile;
use App\Models\Transaction;
use App\Models\Notification;
use App\Models\Card;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ActionController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
    // ===== ADMIN ACTIONS =====

    public function storeCustomer(Request $request)
    {
        $request->validate([
            'full_name'          => 'required|string|max:255',
            'email'              => 'required|email|unique:users,email',
            'nik'                => 'required|string|digits:16',
            'mother_maiden_name' => 'required|string',
            'phone_number'       => 'required|string',
            'unit_id'            => 'required|exists:units,id',
            'ktp_image'          => 'required|image|mimes:jpg,jpeg,png|max:2048',
            'selfie_image'       => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ], [
            'nik.digits'         => 'NIK harus terdiri dari 16 digit angka.',
            'email.unique'       => 'Email sudah terdaftar.',
            'ktp_image.required' => 'Foto KTP wajib diupload.',
            'selfie_image.required' => 'Foto selfie dengan KTP wajib diupload.',
            'ktp_image.max'      => 'Ukuran foto KTP maksimal 2MB.',
            'selfie_image.max'   => 'Ukuran foto selfie maksimal 2MB.',
        ]);

        // Check duplicate NIK
        if (CustomerProfile::where('nik', $request->nik)->exists()) {
            return back()->withErrors(['nik' => 'NIK sudah terdaftar.']);
        }

        DB::beginTransaction();
        try {
            // Upload KTP & selfie
            $nikSanitized = preg_replace('/[^a-zA-Z0-9]/', '', $request->nik);
            $ktpPath = $request->file('ktp_image')->storeAs(
                'documents',
                $nikSanitized . '_ktp_image_' . time() . '.' . $request->file('ktp_image')->extension(),
                'public'
            );
            $selfiePath = $request->file('selfie_image')->storeAs(
                'documents',
                $nikSanitized . '_selfie_image_' . time() . '.' . $request->file('selfie_image')->extension(),
                'public'
            );

            $bankId = 'CIF-' . now()->format('Ym') . '-' . rand(100000, 999999);
            $user = User::create([
                'bank_id'       => $bankId,
                'role_id'       => 9,
                'full_name'     => $request->full_name,
                'email'         => $request->email,
                'password_hash' => bcrypt('password123'),
                'phone_number'  => $request->phone_number,
                'status'        => 'ACTIVE',
            ]);

            $gender = $request->gender;
            if ($gender === 'MALE')   $gender = 'L';
            if ($gender === 'FEMALE') $gender = 'P';

            CustomerProfile::create([
                'user_id'            => $user->id,
                'unit_id'            => $request->unit_id,
                'nik'                => $request->nik,
                'mother_maiden_name' => $request->mother_maiden_name,
                'pob'                => $request->pob,
                'dob'                => $request->dob,
                'gender'             => $gender,
                'address_ktp'        => $request->address_ktp,
                'ktp_image_path'     => '/storage/' . $ktpPath,
                'selfie_image_path'  => '/storage/' . $selfiePath,
                'kyc_status'         => 'VERIFIED',
            ]);

            // Generate unique account number
            $accountNumber = '1100' . str_pad($user->id, 6, '0', STR_PAD_LEFT) . rand(100, 999);
            while (Account::where('account_number', $accountNumber)->exists()) {
                $accountNumber = '1100' . str_pad($user->id, 6, '0', STR_PAD_LEFT) . rand(100, 999);
            }
            Account::create([
                'user_id'        => $user->id,
                'account_number' => $accountNumber,
                'account_type'   => 'TABUNGAN',
                'balance'        => 0,
                'status'         => 'ACTIVE',
            ]);

            DB::commit();
            return redirect('/admin/customers')->with('success', 'Nasabah baru berhasil ditambahkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            // Clean up uploaded files on failure
            if (isset($ktpPath))    \Illuminate\Support\Facades\Storage::disk('public')->delete($ktpPath);
            if (isset($selfiePath)) \Illuminate\Support\Facades\Storage::disk('public')->delete($selfiePath);
            return back()->withErrors(['error' => 'Gagal menambahkan nasabah: ' . $e->getMessage()]);
        }
    }

    public function updateCustomer(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $user->update($request->only(['full_name', 'email', 'phone_number', 'status']));
        if ($user->customerProfile) {
            $user->customerProfile->update($request->only(['nik', 'mother_maiden_name', 'pob', 'dob', 'gender', 'address_ktp', 'unit_id']));
        }
        return redirect("/admin/customers/{$id}")->with('success', 'Data nasabah berhasil diperbarui.');
    }

    public function updateCustomerStatus(Request $request, $id)
    {
        User::where('id', $id)->update(['status' => $request->new_status]);
        return back()->with('success', 'Status nasabah berhasil diperbarui.');
    }

    public function updateLoanStatus(Request $request, $id)
    {
        $loan = Loan::findOrFail($id);
        $oldStatus = $loan->status;
        $loan->update(['status' => $request->status, 'approved_by' => Auth::id(), 'approved_at' => now()]);
        if ($request->status === 'REJECTED' && $request->rejection_reason) {
            $loan->update(['rejection_reason' => $request->rejection_reason]);
        }
        
        // Send notification to customer
        if ($request->status === 'APPROVED') {
            $this->notificationService->notifyUser(
                $loan->user_id,
                'Pinjaman Disetujui',
                'Pengajuan pinjaman Anda sebesar Rp ' . number_format($loan->loan_amount, 0, ',', '.') . ' telah disetujui. Menunggu pencairan dana.'
            );
        } elseif ($request->status === 'REJECTED') {
            $this->notificationService->notifyUser(
                $loan->user_id,
                'Pinjaman Ditolak',
                'Pengajuan pinjaman Anda ditolak. Alasan: ' . ($request->rejection_reason ?? 'Tidak memenuhi syarat.')
            );
        }
        
        return back()->with('success', 'Status pinjaman berhasil diperbarui.');
    }

    public function disburseLoan(Request $request, $id)
    {
        $loan = Loan::findOrFail($id);
        $account = Account::where('user_id', $loan->user_id)->where('account_type', 'TABUNGAN')->first();
        if (!$account) return back()->withErrors(['error' => 'Rekening tabungan tidak ditemukan.']);

        DB::beginTransaction();
        try {
            $account->increment('balance', $loan->loan_amount);
            $loan->update(['status' => 'DISBURSED', 'disbursed_at' => now(), 'disbursed_by' => Auth::id()]);
            Transaction::create([
                'transaction_code' => 'TRX-' . time() . '-' . rand(100000, 999999),
                'to_account_id' => $account->id, 'transaction_type' => 'LOAN_DISBURSEMENT',
                'amount' => $loan->loan_amount, 'fee' => 0,
                'description' => 'Pencairan Pinjaman ' . ($loan->loanProduct?->product_name ?? ''),
                'status' => 'SUCCESS',
            ]);

            // Generate installments if not already created
            if ($loan->installments()->count() === 0) {
                $this->generateInstallments($loan);
            }
            
            // Send notification to customer
            $this->notificationService->notifyUser(
                $loan->user_id,
                'Pinjaman Dicairkan',
                'Dana pinjaman sebesar Rp ' . number_format($loan->loan_amount, 0, ',', '.') . ' telah dicairkan ke rekening Anda.'
            );
            
            DB::commit();
            return back()->with('success', 'Pinjaman berhasil dicairkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Gagal mencairkan: ' . $e->getMessage()]);
        }
    }

    private function generateInstallments(\App\Models\Loan $loan): void
    {
        $installmentAmount = $loan->monthly_installment;
        $remainingPrincipal = $loan->loan_amount;
        $tenorUnit = $loan->tenor_unit ?? 'BULAN';

        $periodRate = $tenorUnit === 'MINGGU'
            ? $loan->interest_rate_pa / 100 / 52
            : $loan->interest_rate_pa / 100 / 12;

        for ($i = 1; $i <= $loan->tenor; $i++) {
            $dueDate = $tenorUnit === 'MINGGU'
                ? now()->addWeeks($i)
                : now()->addMonths($i);

            $interest = round($remainingPrincipal * $periodRate, 2);
            $principal = round($installmentAmount - $interest, 2);

            if ($i === $loan->tenor) {
                $principal = round($remainingPrincipal, 2);
                $interest = round($installmentAmount - $principal, 2);
                if ($interest < 0) $interest = 0;
            }

            \App\Models\LoanInstallment::create([
                'loan_id' => $loan->id,
                'installment_number' => $i,
                'due_date' => $dueDate,
                'principal_amount' => $principal,
                'interest_amount' => $interest,
                'total_amount' => $principal + $interest,
                'status' => 'PENDING',
            ]);

            $remainingPrincipal = round($remainingPrincipal - $principal, 2);
        }
    }

    public function storeLoanProduct(Request $request)
    {
        $request->validate([
            'product_name' => 'required|string|max:255',
            'min_amount' => 'required|numeric|min:0',
            'max_amount' => 'required|numeric|gt:min_amount',
            'interest_rate_pa' => 'required|numeric|min:0|max:100',
            'min_tenor' => 'required|integer|min:1',
            'max_tenor' => 'required|integer|gte:min_tenor',
            'tenor_unit' => 'required|in:BULAN,MINGGU',
            'is_active' => 'sometimes|boolean',
        ]);
        LoanProduct::create($request->only([
            'product_name', 'min_amount', 'max_amount', 'interest_rate_pa',
            'min_tenor', 'max_tenor', 'tenor_unit', 'is_active', 'description',
        ]));
        return back()->with('success', 'Produk pinjaman berhasil ditambahkan.');
    }

    public function updateLoanProduct(Request $request, $id)
    {
        $request->validate([
            'product_name' => 'sometimes|string|max:255',
            'min_amount' => 'sometimes|numeric|min:0',
            'max_amount' => 'sometimes|numeric',
            'interest_rate_pa' => 'sometimes|numeric|min:0|max:100',
            'min_tenor' => 'sometimes|integer|min:1',
            'max_tenor' => 'sometimes|integer|min:1',
            'tenor_unit' => 'sometimes|in:BULAN,MINGGU',
            'is_active' => 'sometimes|boolean',
        ]);
        LoanProduct::findOrFail($id)->update($request->only([
            'product_name', 'min_amount', 'max_amount', 'interest_rate_pa',
            'min_tenor', 'max_tenor', 'tenor_unit', 'is_active', 'description',
        ]));
        return back()->with('success', 'Produk pinjaman berhasil diperbarui.');
    }

    public function deleteLoanProduct($id)
    {
        $product = LoanProduct::findOrFail($id);

        $activeLoansCount = $product->loans()
            ->whereIn('status', ['SUBMITTED', 'APPROVED', 'DISBURSED', 'ACTIVE'])
            ->count();

        if ($activeLoansCount > 0) {
            return back()->with('error', 'Tidak dapat menghapus produk yang masih memiliki pinjaman aktif.');
        }

        $product->delete();
        return back()->with('success', 'Produk pinjaman berhasil dihapus.');
    }

    public function storeDepositProduct(Request $request)
    {
        $request->validate([
            'product_name' => 'required|string|max:255',
            'min_amount' => 'required|numeric|min:0',
            'max_amount' => 'nullable|numeric',
            'interest_rate_pa' => 'required|numeric|min:0|max:100',
            'tenor_months' => 'required|integer|min:1',
            'is_active' => 'sometimes|boolean',
        ]);
        DepositProduct::create($request->only([
            'product_name', 'min_amount', 'max_amount', 'interest_rate_pa',
            'tenor_months', 'is_active', 'description',
        ]));
        return back()->with('success', 'Produk deposito berhasil ditambahkan.');
    }

    public function updateDepositProduct(Request $request, $id)
    {
        $request->validate([
            'product_name' => 'sometimes|string|max:255',
            'min_amount' => 'sometimes|numeric|min:0',
            'max_amount' => 'nullable|numeric',
            'interest_rate_pa' => 'sometimes|numeric|min:0|max:100',
            'tenor_months' => 'sometimes|integer|min:1',
            'is_active' => 'sometimes|boolean',
        ]);
        DepositProduct::findOrFail($id)->update($request->only([
            'product_name', 'min_amount', 'max_amount', 'interest_rate_pa',
            'tenor_months', 'is_active', 'description',
        ]));
        return back()->with('success', 'Produk deposito berhasil diperbarui.');
    }

    public function storeStaff(Request $request)
    {
        $request->validate(['full_name' => 'required', 'email' => 'required|email|unique:users,email', 'role_id' => 'required']);
        $tempPassword = 'Staff' . rand(1000, 9999);
        $bankId = 'NIP-' . now()->format('Ym') . '-' . rand(100000, 999999);
        User::create([
            'bank_id' => $bankId, 'role_id' => $request->role_id, 'full_name' => $request->full_name,
            'email' => $request->email, 'phone_number' => $request->phone_number ?? '0000000000',
            'password_hash' => bcrypt($tempPassword), 'status' => 'ACTIVE',
        ]);
        return back()->with('success', "Staf berhasil dibuat. Password sementara: {$tempPassword}");
    }

    public function updateStaff(Request $request, $id)
    {
        User::findOrFail($id)->update($request->only(['full_name', 'email', 'role_id']));
        return back()->with('success', 'Data staf berhasil diperbarui.');
    }

    public function updateStaffStatus(Request $request, $id)
    {
        User::findOrFail($id)->update(['status' => $request->new_status]);
        return back()->with('success', 'Status staf berhasil diperbarui.');
    }

    public function resetStaffPassword($id)
    {
        $tempPassword = 'Reset' . rand(1000, 9999);
        User::findOrFail($id)->update(['password_hash' => bcrypt($tempPassword)]);
        return back()->with('success', "Password berhasil direset. Password baru: {$tempPassword}");
    }

    public function storeUnit(Request $request)
    {
        $request->validate([
            'unit_name' => 'required|string|max:255',
            'unit_type' => 'required|in:KANTOR_CABANG,KANTOR_KAS',
            'parent_id' => 'nullable|exists:units,id',
        ]);
        
        // Validate unit type rules
        if ($request->unit_type === 'KANTOR_KAS' && empty($request->parent_id)) {
            return back()->withErrors(['parent_id' => 'Kantor Kas harus berada di bawah sebuah Kantor Cabang.']);
        }
        
        DB::table('units')->insert([
            'unit_name' => $request->unit_name,
            'unit_code' => strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $request->unit_name), 0, 5)) . '-' . rand(10, 99),
            'unit_type' => $request->unit_type,
            'parent_id' => $request->unit_type === 'KANTOR_KAS' ? (int)$request->parent_id : null,
            'address' => $request->address,
            'status' => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return back()->with('success', 'Unit berhasil ditambahkan.');
    }

    public function updateUnit(Request $request, $id)
    {
        $data = array_filter([
            'unit_name' => $request->unit_name,
            'address' => $request->address,
            'unit_type' => $request->unit_type,
            'status' => $request->status,
        ], fn($v) => $v !== null);
        $data['updated_at'] = now();
        DB::table('units')->where('id', $id)->update($data);
        return back()->with('success', 'Unit berhasil diperbarui.');
    }

    public function deleteUnit($id)
    {
        $user = Auth::user();
        
        // Only Super Admin can delete units
        if ($user->role_id !== 1) {
            return back()->withErrors(['error' => 'Akses ditolak.']);
        }
        
        DB::table('units')->where('id', $id)->delete();
        return back()->with('success', 'Unit berhasil dihapus.');
    }

    // ===== USER ACTIONS =====

    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        if ($user->customerProfile) {
            $user->customerProfile->update($request->only(['address_domicile', 'occupation']));
        }
        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function changePassword(Request $request)
    {
        $request->validate(['current_password' => 'required', 'new_password' => 'required|min:6']);
        $user = Auth::user();
        if (!Hash::check($request->current_password, $user->password_hash)) {
            return back()->withErrors(['current_password' => 'Password saat ini salah.']);
        }
        $user->update(['password_hash' => bcrypt($request->new_password)]);
        return back()->with('success', 'Password berhasil diperbarui.');
    }

    public function changePin(Request $request)
    {
        $request->validate(['new_pin' => 'required|digits:6']);
        $user = Auth::user();
        if ($user->pin_hash) {
            if (!$request->old_pin) {
                return back()->withErrors(['old_pin' => 'PIN lama wajib diisi.']);
            }
            if (!Hash::check($request->old_pin, $user->pin_hash)) {
                return back()->withErrors(['old_pin' => 'PIN lama salah.']);
            }
        }
        $user->update(['pin_hash' => bcrypt($request->new_pin)]);
        return back()->with('success', 'PIN berhasil diperbarui.');
    }

    public function internalTransfer(Request $request)
    {
        $request->validate(['destination_account_number' => 'required', 'amount' => 'required|numeric|min:1000', 'pin' => 'required']);
        $user = Auth::user();
        if (!$user->pin_hash) return back()->withErrors(['pin' => 'Anda belum mengatur PIN transaksi.']);
        if (!Hash::check($request->pin, $user->pin_hash)) return back()->withErrors(['pin' => 'PIN salah.']);

        $fromAccount = $user->accounts()->where('account_type', 'TABUNGAN')->first();
        $toAccount = Account::where('account_number', $request->destination_account_number)->first();
        if (!$fromAccount) return back()->withErrors(['error' => 'Rekening tabungan tidak ditemukan.']);
        if (!$toAccount) return back()->withErrors(['destination_account_number' => 'Rekening tujuan tidak ditemukan.']);
        if ($fromAccount->balance < $request->amount) return back()->withErrors(['amount' => 'Saldo tidak mencukupi.']);

        DB::beginTransaction();
        try {
            $fromAccount->decrement('balance', $request->amount);
            $toAccount->increment('balance', $request->amount);
            $txCode = 'TRX-' . time() . '-' . rand(100000, 999999);
            Transaction::create([
                'transaction_code' => $txCode, 'from_account_id' => $fromAccount->id, 'to_account_id' => $toAccount->id,
                'transaction_type' => 'TRANSFER_INTERNAL', 'amount' => $request->amount, 'fee' => 0,
                'description' => $request->description ?? 'Transfer ke ' . $toAccount->user->full_name, 'status' => 'SUCCESS',
            ]);
            DB::commit();
            return back()->with('success', 'Transfer berhasil.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Transfer gagal: ' . $e->getMessage()]);
        }
    }

    public function transferInquiry(Request $request)
    {
        $account = Account::where('account_number', $request->destination_account_number)->with('user')->first();
        if (!$account) return response()->json(['status' => 'error', 'message' => 'Rekening tidak ditemukan.'], 404);
        return response()->json(['status' => 'success', 'data' => ['recipient_name' => $account->user->full_name, 'account_number' => $account->account_number]]);
    }

    public function markAllNotificationsRead()
    {
        Notification::where('user_id', Auth::id())->where('is_read', false)->update(['is_read' => true]);
        return back()->with('success', 'Semua notifikasi telah dibaca.');
    }

    public function addWithdrawalAccount(Request $request)
    {
        DB::table('withdrawal_accounts')->insert([
            'user_id' => Auth::id(), 'bank_name' => $request->bank_name,
            'account_number' => $request->account_number, 'account_name' => $request->account_name,
            'created_at' => now(),
        ]);
        return back()->with('success', 'Rekening penarikan berhasil ditambahkan.');
    }

    public function deleteWithdrawalAccount($id)
    {
        DB::table('withdrawal_accounts')->where('id', $id)->where('user_id', Auth::id())->delete();
        return back()->with('success', 'Rekening penarikan berhasil dihapus.');
    }

    public function createWithdrawalRequest(Request $request)
    {
        $request->validate([
            'withdrawal_account_id' => 'required|exists:withdrawal_accounts,id',
            'amount' => 'required|numeric|min:10000',
            'pin' => 'required|string',
        ]);

        $user = Auth::user();

        // Verify PIN
        if ($user->pin_hash && !Hash::check($request->pin, $user->pin_hash)) {
            return back()->withErrors(['pin' => 'PIN transaksi salah.']);
        }

        // Check balance
        $account = \App\Models\Account::where('user_id', $user->id)
            ->where('account_type', 'TABUNGAN')
            ->where('status', 'ACTIVE')
            ->first();

        if (!$account || $account->balance < $request->amount) {
            return back()->withErrors(['amount' => 'Saldo tidak mencukupi.']);
        }

        // Verify withdrawal account belongs to user
        $withdrawalAccount = DB::table('withdrawal_accounts')
            ->where('id', $request->withdrawal_account_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$withdrawalAccount) {
            return back()->withErrors(['withdrawal_account_id' => 'Rekening penarikan tidak valid.']);
        }

        DB::table('withdrawal_requests')->insert([
            'user_id' => $user->id,
            'withdrawal_account_id' => $request->withdrawal_account_id,
            'amount' => $request->amount,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Permintaan penarikan berhasil dikirim.');
    }

    public function addBeneficiary(Request $request)
    {
        DB::table('beneficiaries')->insert([
            'user_id' => Auth::id(), 'nickname' => $request->nickname,
            'beneficiary_account_number' => $request->account_number,
            'beneficiary_name' => $request->beneficiary_name ?? $request->nickname,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return back()->with('success', 'Penerima berhasil ditambahkan.');
    }

    public function deleteBeneficiary($id)
    {
        DB::table('beneficiaries')->where('id', $id)->where('user_id', Auth::id())->delete();
        return back()->with('success', 'Penerima berhasil dihapus.');
    }

    public function submitLoanApplication(Request $request)
    {
        $request->validate([
            'loan_product_id' => 'required|exists:loan_products,id',
            'amount' => 'required|numeric|min:0',
            'tenor' => 'required|integer|min:1',
            'purpose' => 'required|string|max:500',
        ]);

        $product = LoanProduct::findOrFail($request->loan_product_id);

        if ($request->amount < $product->min_amount || $request->amount > $product->max_amount) {
            return back()->withErrors(['amount' => "Jumlah pinjaman harus antara Rp " . number_format($product->min_amount, 0, ',', '.') . " dan Rp " . number_format($product->max_amount, 0, ',', '.') . "."]);
        }

        if ($request->tenor < $product->min_tenor || $request->tenor > $product->max_tenor) {
            return back()->withErrors(['tenor' => "Tenor harus antara {$product->min_tenor} dan {$product->max_tenor} {$product->tenor_unit}."]);
        }

        $user = Auth::user();

        $interestRate = $product->interest_rate_pa / 100 / 12;
        $tenor = (int) $request->tenor;
        $amount = (float) $request->amount;

        if ($interestRate > 0) {
            $monthlyInstallment = ($amount * $interestRate * pow(1 + $interestRate, $tenor)) / (pow(1 + $interestRate, $tenor) - 1);
        } else {
            $monthlyInstallment = $amount / $tenor;
        }

        $totalInterest = ($monthlyInstallment * $tenor) - $amount;
        $totalRepayment = $amount + $totalInterest;

        $loan = Loan::create([
            'user_id' => $user->id,
            'loan_product_id' => $product->id,
            'loan_amount' => $amount,
            'interest_rate_pa' => $product->interest_rate_pa,
            'tenor' => $tenor,
            'tenor_unit' => $product->tenor_unit,
            'monthly_installment' => round($monthlyInstallment, 2),
            'total_interest' => round($totalInterest, 2),
            'total_repayment' => round($totalRepayment, 2),
            'purpose' => $request->purpose,
            'status' => 'SUBMITTED',
        ]);

        // Notify customer
        $this->notificationService->notifyUser(
            $user->id,
            'Pengajuan Pinjaman Diterima',
            'Pengajuan pinjaman Anda sebesar Rp ' . number_format($amount, 0, ',', '.') . ' telah diterima dan sedang diproses.'
        );

        // Notify admin staff
        $this->notificationService->notifyStaffByRole(
            [1, 2, 3], // Super Admin, Admin, Manager
            'Pengajuan Pinjaman Baru',
            'Pengajuan pinjaman baru dari ' . $user->full_name . ' sebesar Rp ' . number_format($amount, 0, ',', '.') . ' menunggu persetujuan.'
        );

        return redirect('/my-loans')->with('success', 'Pengajuan pinjaman berhasil dikirim.');
    }
}
