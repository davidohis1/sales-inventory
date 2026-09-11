/* Shared 6-digit code entry behaviour for the verify-email / reset-password
   steps on the login, register, and forgot-password pages. Handles
   auto-advance between boxes, backspace-to-previous, pasting a full code,
   and a 30-second cooldown on the "Resend code" button. */
window.BizflowOtp = (function () {
    function wire(formId, getContext, handlers) {
        const form = document.getElementById(formId);
        if (!form) return;
        const boxes = [...form.querySelectorAll('.tz-otp-row input')];

        boxes.forEach((box, i) => {
            box.addEventListener('input', () => {
                box.value = box.value.replace(/[^0-9]/g, '').slice(0, 1);
                if (box.value && i < boxes.length - 1) boxes[i + 1].focus();
            });
            box.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !box.value && i > 0) boxes[i - 1].focus();
            });
            box.addEventListener('paste', (e) => {
                e.preventDefault();
                const digits = (e.clipboardData.getData('text') || '').replace(/[^0-9]/g, '').slice(0, boxes.length).split('');
                digits.forEach((d, idx) => { if (boxes[idx]) boxes[idx].value = d; });
                const next = boxes[Math.min(digits.length, boxes.length - 1)];
                if (next) next.focus();
            });
        });

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const code = boxes.map((b) => b.value).join('');
            if (code.length !== boxes.length) return;
            handlers.onSubmit(code, getContext());
        });

        const resendBtn = form.parentElement.querySelector('#resend-btn') || document.getElementById('resend-btn');
        if (resendBtn && handlers.onResend) {
            resendBtn.addEventListener('click', async () => {
                resendBtn.disabled = true;
                let seconds = 30;
                resendBtn.textContent = `Resend code (${seconds}s)`;
                await handlers.onResend(getContext());
                const timer = setInterval(() => {
                    seconds -= 1;
                    if (seconds <= 0) {
                        clearInterval(timer);
                        resendBtn.disabled = false;
                        resendBtn.textContent = 'Resend code';
                        return;
                    }
                    resendBtn.textContent = `Resend code (${seconds}s)`;
                }, 1000);
            });
        }

        return { boxes, focusFirst: () => boxes[0] && boxes[0].focus(), clear: () => boxes.forEach((b) => (b.value = '')) };
    }

    return { wire };
})();
