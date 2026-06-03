<?php

// ============================================
// PHP Translation Service
// Single source of truth for all PHP-side strings
// Usage: I18n::t('key', 'pl')  or  I18n::t('key', 'pl', ['item' => 'value'])
// ============================================

class I18n {

    private static array $strings = [
        'pl' => [
            // ── Auth / login ───────────────────────────────────────────────────
            'err_fields_required'    => 'Email i hasło są wymagane.',
            'err_invalid_credentials'=> 'Błędny email lub hasło.',
            'login_success'          => 'Zalogowano pomyślnie.',

            // ── Account status messages (shown at login) ───────────────────────
            'status_waiting_confirm' => 'Potwierdź swój adres email — sprawdź skrzynkę pocztową.',
            'status_waiting_approve' => 'Twoje konto czeka na zatwierdzenie przez administratora.',
            'status_deleted'         => 'To konto zostało usunięte.',
            'status_inactive'        => 'Konto nie jest aktywne.',

            // ── Registration ───────────────────────────────────────────────────
            'reg_invalid_email'      => 'Podaj prawidłowy adres email.',
            'reg_pass_too_short'     => 'Hasło musi mieć co najmniej 8 znaków.',
            'reg_pass_mismatch'      => 'Hasła nie są zgodne.',
            'reg_email_exists'       => 'Konto z tym adresem email już istnieje.',
            'reg_failed'             => 'Nie udało się utworzyć konta. Spróbuj ponownie.',
            'reg_success'            => 'Konto zostało utworzone! Sprawdź email, aby potwierdzić adres.',

            // ── Email subjects ─────────────────────────────────────────────────
            'email_confirm_subject'  => 'Potwierdź email – Kuba',
            'email_approved_subject' => 'Twoje konto zostało zatwierdzone – Kuba',
            'feedback_subject_bug'   => 'Kuba – zgłoszenie błędu',
            'feedback_subject_feature' => 'Kuba – propozycja nowej funkcji',
            'feedback_subject_existing' => 'Kuba – opinia o istniejącej funkcji',

            // ── Feedback form ──────────────────────────────────────────────────
            'feedback_type_bug'      => 'Błąd',
            'feedback_type_feature'  => 'Propozycja nowej funkcji',
            'feedback_type_existing' => 'Opinia o istniejącej funkcji',
            'feedback_email_greeting' => 'Nowa wiadomość z formularza opinii:',
            'feedback_email_intro'   => 'Użytkownik wysłał wiadomość z formularza opinii. Poniżej szczegóły:',
            'feedback_email_sender'  => 'Nadawca',
            'feedback_email_type'    => 'Rodzaj',
            'feedback_email_language' => 'Język',
            'feedback_email_role'    => 'Rola',
            'feedback_email_message' => 'Treść wiadomości',
            'feedback_email_button'  => 'Otwórz aplikację',
            'feedback_email_footer'  => 'Odpowiadając na tę wiadomość, wyślesz odpowiedź bezpośrednio do nadawcy.',
            'feedback_sent'          => 'Dziękujemy! Twoja wiadomość została wysłana do administratora.',
            'feedback_failed'        => 'Nie udało się wysłać wiadomości. Spróbuj ponownie.',
            'feedback_invalid_type'   => 'Wybierz prawidłowy rodzaj wiadomości.',
            'feedback_invalid_message'=> 'Wpisz treść wiadomości.',

            // ── Confirm-email page headings ────────────────────────────────────
            'confirm_invalid_link'   => 'Nieprawidłowy link',
            'confirm_invalid_body'   => 'Link potwierdzający jest nieważny lub wygasł.',
            'confirm_already_done'   => 'Już przetworzone',
            'confirm_already_body'   => 'Ten link został już użyty.',
            'confirm_error'          => 'Błąd',
            'confirm_error_body'     => 'Nie udało się potwierdzić emaila. Spróbuj ponownie.',
            'confirm_success'        => '✅ Email potwierdzony!',
            'confirm_success_body'   => 'Twój email został potwierdzony. Konto czeka teraz na zatwierdzenie przez administratora. Administrator ma 24 godziny na zatwierdzenie konta — otrzymasz powiadomienie emailem.',
            'back_to_login'          => '← Wróć do logowania',

            // ── Approval-email page headings ───────────────────────────────────
            'approve_invalid_link'   => 'Nieprawidłowy link',
            'approve_invalid_body'   => 'Link zatwierdzający jest nieprawidłowy lub został już użyty.',
            'approve_already_done'   => 'Już przetworzone',
            'approve_success'        => '✅ Konto zatwierdzone!',

            // ── Email bodies (HTML) — use buildXxx helpers in mailer ───────────
            'email_confirm_greeting' => 'Witaj!',
            'email_confirm_intro'    => 'Na ten adres email zostało zarejestrowane konto. Aby dokończyć rejestrację, potwierdź swój adres email:',
            'email_confirm_btn'      => '✅ Potwierdź adres email',
            'email_confirm_footer'   => 'Jeśli nie zakładałeś/aś konta, zignoruj ten email.',
            'email_approved_greeting'=> 'Świetna wiadomość!',
            'email_approved_intro'   => 'Twoje konto zostało zatwierdzone. Możesz się teraz zalogować:',
            'email_approved_btn'     => '🚀 Zaloguj się',
        ],

        'en' => [
            // ── Auth / login ───────────────────────────────────────────────────
            'err_fields_required'    => 'Email and password are required.',
            'err_invalid_credentials'=> 'Invalid email or password.',
            'login_success'          => 'Logged in successfully.',

            // ── Account status messages ────────────────────────────────────────
            'status_waiting_confirm' => 'Please confirm your email address — check your inbox.',
            'status_waiting_approve' => 'Your account is waiting for admin approval.',
            'status_deleted'         => 'This account has been deleted.',
            'status_inactive'        => 'Account is not active.',

            // ── Registration ───────────────────────────────────────────────────
            'reg_invalid_email'      => 'Please enter a valid email address.',
            'reg_pass_too_short'     => 'Password must be at least 8 characters.',
            'reg_pass_mismatch'      => 'Passwords do not match.',
            'reg_email_exists'       => 'An account with this email already exists.',
            'reg_failed'             => 'Failed to create account. Please try again.',
            'reg_success'            => 'Account created! Check your email to confirm your address.',

            // ── Email subjects ─────────────────────────────────────────────────
            'email_confirm_subject'  => 'Confirm your email – Kuba',
            'email_approved_subject' => 'Your account has been approved – Kuba',
            'feedback_subject_bug'   => 'Kuba – bug report',
            'feedback_subject_feature' => 'Kuba – new feature suggestion',
            'feedback_subject_existing' => 'Kuba – feedback about an existing feature',

            // ── Feedback form ──────────────────────────────────────────────────
            'feedback_type_bug'      => 'Bug',
            'feedback_type_feature'  => 'New feature suggestion',
            'feedback_type_existing' => 'Feedback about an existing feature',
            'feedback_email_greeting' => 'You have received a new feedback message:',
            'feedback_email_intro'   => 'A user has sent a message through the feedback form. Details below:',
            'feedback_email_sender'  => 'Sender',
            'feedback_email_type'    => 'Type',
            'feedback_email_language' => 'Language',
            'feedback_email_role'    => 'Role',
            'feedback_email_message' => 'Message',
            'feedback_email_button'  => 'Open app',
            'feedback_email_footer'  => 'Replying to this email will send your response directly to the sender.',
            'feedback_sent'          => 'Thanks! Your message has been sent to the administrator.',
            'feedback_failed'        => 'Could not send the message. Please try again.',
            'feedback_invalid_type'   => 'Please choose a valid message type.',
            'feedback_invalid_message'=> 'Please enter a message.',

            // ── Confirm-email page headings ────────────────────────────────────
            'confirm_invalid_link'   => 'Invalid link',
            'confirm_invalid_body'   => 'The confirmation link is invalid or has expired.',
            'confirm_already_done'   => 'Already processed',
            'confirm_already_body'   => 'This link has already been used.',
            'confirm_error'          => 'Error',
            'confirm_error_body'     => 'Failed to confirm email. Please try again.',
            'confirm_success'        => '✅ Email confirmed!',
            'confirm_success_body'   => 'Your email has been confirmed. Your account is now waiting for admin approval. The administrator has 24 hours to approve your account — you will receive a notification by email.',
            'back_to_login'          => '← Back to login',

            // ── Approval-email page headings ───────────────────────────────────
            'approve_invalid_link'   => 'Invalid link',
            'approve_invalid_body'   => 'The approval link is invalid or has already been used.',
            'approve_already_done'   => 'Already processed',
            'approve_success'        => '✅ Account approved!',

            // ── Email bodies ───────────────────────────────────────────────────
            'email_confirm_greeting' => 'Hello!',
            'email_confirm_intro'    => 'You have registered an account with this email address. To complete registration, please confirm your email:',
            'email_confirm_btn'      => '✅ Confirm email address',
            'email_confirm_footer'   => 'If you did not create this account, please ignore this email.',
            'email_approved_greeting'=> 'Great news!',
            'email_approved_intro'   => 'Your account has been approved. You can now log in:',
            'email_approved_btn'     => '🚀 Log in',
        ],
    ];

    /**
     * Translate a key, substituting {{placeholder}} variables.
     *
     * @param string $key   Translation key
     * @param string $lang  Language code ('pl' | 'en')
     * @param array  $vars  Key → value substitutions
     */
    public static function t(string $key, string $lang = 'pl', array $vars = []): string {
        $lang = in_array($lang, ['pl', 'en']) ? $lang : 'pl';
        $str  = self::$strings[$lang][$key]
             ?? self::$strings['pl'][$key]
             ?? $key;
        foreach ($vars as $k => $v) {
            $str = str_replace('{{' . $k . '}}', (string)$v, $str);
        }
        return $str;
    }

    /**
     * Resolve an account status code to a user-facing message.
     */
    public static function statusMessage(string $status, string $lang = 'pl'): string {
        $map = [
            'WAITING_FOR_CONFIRMATION' => 'status_waiting_confirm',
            'WAITING_FOR_APPROVAL'     => 'status_waiting_approve',
            'DELETED'                  => 'status_deleted',
        ];
        return self::t($map[$status] ?? 'status_inactive', $lang);
    }
}
?>

