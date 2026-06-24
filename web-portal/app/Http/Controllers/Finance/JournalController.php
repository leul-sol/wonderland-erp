<?php

namespace App\Http\Controllers\Finance;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\HandlesPortalApiErrors;
use App\Http\Controllers\Controller;
use App\Services\Api\S4FinanceClient;
use App\Services\Auth\PortalAuthService;
use App\Support\JournalApprovalSteps;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class JournalController extends Controller
{
    use HandlesPortalApiErrors;

    public function __construct(
        private readonly S4FinanceClient $s4,
        private readonly PortalAuthService $auth,
    ) {
    }

    public function index(): Response|RedirectResponse
    {
        try {
            $response = $this->s4->journalEntries(['source_module' => 'manual', 'per_page' => 50]);
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'dashboard');
        }

        $entries = $response['data'] ?? [];
        if (isset($entries['data']) && is_array($entries['data'])) {
            $entries = $entries['data'];
        }

        return Inertia::render('Finance/Journals/Index', [
            'journalEntries' => is_array($entries) ? $entries : [],
            'canCreate' => $this->auth->hasAnyPermission(['S4.finance.journal_entries.create']),
        ]);
    }

    public function create(): Response|RedirectResponse
    {
        try {
            $accounts = $this->s4->accounts();
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'finance.journals.index');
        }

        return Inertia::render('Finance/Journals/Create', [
            'accounts' => $accounts['data'] ?? [],
            'defaultEntryDate' => now()->toDateString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'entry_date' => ['nullable', 'date'],
            'source_reference' => ['nullable', 'string', 'max:100'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_code' => ['required', 'string', 'max:20'],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
        ]);

        $lines = collect($data['lines'])->map(fn (array $line) => [
            'account_code' => $line['account_code'],
            'debit' => (float) ($line['debit'] ?? 0),
            'credit' => (float) ($line['credit'] ?? 0),
            'description' => $line['description'] ?? null,
        ])->values()->all();

        try {
            $response = $this->s4->createJournalEntry([
                'description' => $data['description'],
                'entry_date' => $data['entry_date'] ?? now()->toDateString(),
                'source_module' => 'manual',
                'source_reference' => $data['source_reference'] ?? null,
                'lines' => $lines,
            ], (string) Str::uuid());
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        $entryId = (int) ($response['data']['id'] ?? 0);

        if ($entryId <= 0) {
            return back()->with('error', 'Journal entry was not created.');
        }

        return redirect()
            ->route('finance.journals.show', $entryId)
            ->with('success', 'Manual journal saved as draft.');
    }

    public function show(int $journalEntry): Response|RedirectResponse
    {
        try {
            $response = $this->s4->journalEntry($journalEntry);
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'finance.journals.index');
        }

        $entry = $response['data'] ?? [];
        $requiresGm = JournalApprovalSteps::requiresGm($entry);
        $status = (string) ($entry['status'] ?? '');

        return Inertia::render('Finance/Journals/Show', [
            'journalEntry' => $entry,
            'approvalSteps' => JournalApprovalSteps::steps($requiresGm),
            'approvalCurrentStep' => JournalApprovalSteps::currentStepKey($entry),
            'gmThreshold' => JournalApprovalSteps::GM_THRESHOLD,
            'canApproveFinance' => $status === 'draft' && $this->auth->hasAnyPermission(['S4.finance.journal_entries.approve']),
            'canApproveGm' => $status === 'approved'
                && $requiresGm
                && empty($entry['second_approved_by'])
                && $this->auth->hasAnyPermission(['S4.finance.journal_entries.approve']),
            'canDelete' => $status === 'draft' && $this->auth->hasAnyPermission(['S4.finance.journal_entries.create']),
        ]);
    }

    public function approve(int $journalEntry): RedirectResponse
    {
        try {
            $this->s4->approveJournalEntry($journalEntry);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Journal approval recorded.');
    }

    public function destroy(int $journalEntry): RedirectResponse
    {
        try {
            $this->s4->deleteJournalEntry($journalEntry);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return redirect()
            ->route('finance.journals.index')
            ->with('success', 'Draft journal deleted.');
    }
}
