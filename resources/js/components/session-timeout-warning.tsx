import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { type SharedData } from '@/types';
import { router, usePage } from '@inertiajs/react';
import { Clock } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';

const WARNING_LEAD_MINUTES = 2;

export function SessionTimeoutWarning() {
    const { sessionLifetime, auth } = usePage<SharedData>().props;
    const lifetimeMinutes = Math.max(1, sessionLifetime || 30);
    const totalMs = lifetimeMinutes * 60 * 1000;
    const warnAfterMs = Math.max(0, (lifetimeMinutes - WARNING_LEAD_MINUTES) * 60 * 1000);

    const lastActivityRef = useRef<number>(Date.now());
    const loggedOutRef = useRef<boolean>(false);
    const [warningOpen, setWarningOpen] = useState(false);
    const [remainingSeconds, setRemainingSeconds] = useState(WARNING_LEAD_MINUTES * 60);
    const [refreshing, setRefreshing] = useState(false);

    const resetActivity = useCallback(() => {
        lastActivityRef.current = Date.now();
        loggedOutRef.current = false;
        setWarningOpen(false);
    }, []);

    useEffect(() => {
        if (!auth.user) {
            return;
        }

        let lastTracked = Date.now();
        const handleActivity = () => {
            if (warningOpen) {
                return;
            }
            const now = Date.now();
            if (now - lastTracked >= 5000) {
                lastTracked = now;
                lastActivityRef.current = now;
            }
        };

        const events: (keyof WindowEventMap)[] = ['mousedown', 'keydown', 'touchstart', 'scroll'];
        events.forEach((eventName) => window.addEventListener(eventName, handleActivity, { passive: true }));

        const removeInertiaListener = router.on('finish', () => {
            lastActivityRef.current = Date.now();
            setWarningOpen(false);
        });

        const interval = window.setInterval(() => {
            const elapsed = Date.now() - lastActivityRef.current;

            if (elapsed >= totalMs && !loggedOutRef.current) {
                loggedOutRef.current = true;
                setWarningOpen(false);
                router.post(route('logout'));
                return;
            }

            if (elapsed >= warnAfterMs) {
                const leftSec = Math.max(0, Math.ceil((totalMs - elapsed) / 1000));
                setRemainingSeconds(leftSec);
                setWarningOpen(true);
            }
        }, 1000);

        return () => {
            events.forEach((eventName) => window.removeEventListener(eventName, handleActivity));
            removeInertiaListener();
            window.clearInterval(interval);
        };
    }, [auth.user, totalMs, warnAfterMs, warningOpen]);

    if (!auth.user) {
        return null;
    }

    const minutes = Math.floor(remainingSeconds / 60);
    const seconds = remainingSeconds % 60;
    const formattedCountdown = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;

    const handleKeepAlive = () => {
        setRefreshing(true);
        router.post(
            route('session.keep-alive'),
            {},
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    resetActivity();
                },
                onFinish: () => {
                    setRefreshing(false);
                },
            },
        );
    };

    const handleLogout = () => {
        setWarningOpen(false);
        router.post(route('logout'));
    };

    return (
        <Dialog open={warningOpen} onOpenChange={() => {}}>
            <DialogContent className="sm:max-w-md" onPointerDownOutside={(e) => e.preventDefault()} onEscapeKeyDown={(e) => e.preventDefault()}>
                <DialogHeader>
                    <div className="mb-2 flex items-center gap-2 text-amber-600 dark:text-amber-400">
                        <Clock className="h-5 w-5" />
                        <DialogTitle>Sesi Hampir Berakhir</DialogTitle>
                    </div>
                    <DialogDescription>
                        Tidak ada aktivitas terdeteksi. Demi keamanan data klinis pasien, sesi Anda akan otomatis berakhir dalam{' '}
                        <span className="text-foreground font-semibold">{formattedCountdown}</span>.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter className="gap-2 sm:gap-2">
                    <Button type="button" variant="outline" onClick={handleLogout} disabled={refreshing}>
                        Keluar
                    </Button>
                    <Button type="button" onClick={handleKeepAlive} disabled={refreshing}>
                        {refreshing ? 'Memperpanjang...' : 'Tetap Masuk'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
