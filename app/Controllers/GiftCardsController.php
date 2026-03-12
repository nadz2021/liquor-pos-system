<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\GiftCard;

final class GiftCardsController extends Controller
{
    public function index(): void
    {
        Auth::requireLogin();

        if (!Auth::can('gift_cards.manage')) {
            http_response_code(403);
            echo 'Forbidden';
            return;
        }

        $this->view('giftcards/index', [
            'user' => Auth::user(),
            'giftCards' => GiftCard::list(300),
        ]);
    }

    public function store(): void
    {
        Auth::requireLogin();

        if (!Auth::can('gift_cards.manage')) {
            http_response_code(403);
            echo 'Forbidden';
            return;
        }

        $amount = (float)($_POST['amount'] ?? 0);

        if ($amount <= 0) {
            http_response_code(400);
            echo 'Invalid amount.';
            return;
        }

        GiftCard::create($amount, (int)Auth::user()['id']);

        header('Location: /gift-cards');
        exit;
    }

    public function assign(): void
    {
        Auth::requireLogin();

        if (!Auth::can('gift_cards.manage')) {
            http_response_code(403);
            echo 'Forbidden';
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        $customerId = isset($_POST['customer_id']) && $_POST['customer_id'] !== ''
            ? (int)$_POST['customer_id']
            : null;

        // manual assignment only, not tied to POS sale
        $ok = GiftCard::assign($id, $customerId, null);

        if (!$ok) {
            http_response_code(400);
            echo 'Unable to assign gift card.';
            return;
        }

        header('Location: /gift-cards');
        exit;
    }

    public function check(): void
    {
        Auth::requireLogin();

        if (!Auth::can('pos.use')) {
            $this->json(['ok' => false, 'msg' => 'Forbidden'], 403);
            return;
        }

        $code = trim((string)($_GET['code'] ?? ''));

        if ($code === '') {
            $this->json(['ok' => false, 'msg' => 'Gift card code is required.'], 422);
            return;
        }

        $gc = GiftCard::findByCode($code);

        if (!$gc) {
            $this->json(['ok' => false, 'msg' => 'Gift card not found.'], 404);
            return;
        }

        if (($gc['status'] ?? '') === 'redeemed') {
            $redeemedAt = (string)($gc['redeemed_at'] ?? '');
            $this->json([
                'ok' => false,
                'msg' => "This gift card was already redeemed on {$redeemedAt}.",
                'gift_card' => $gc,
            ], 422);
            return;
        }

        if (($gc['status'] ?? '') !== 'assigned') {
            $this->json([
                'ok' => false,
                'msg' => 'This gift card is not yet assigned.',
                'gift_card' => $gc,
            ], 422);
            return;
        }

        $this->json([
            'ok' => true,
            'gift_card' => $gc,
        ]);
    }
}