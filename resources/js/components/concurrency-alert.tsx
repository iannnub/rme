import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { AlertTriangle, RefreshCw } from 'lucide-react';

interface ConcurrencyAlertProps {
    message?: string;
}

export function ConcurrencyAlert({ message }: ConcurrencyAlertProps) {
    if (!message) {
        return null;
    }

    return (
        <Alert variant="destructive" role="alert" className="border-destructive/60">
            <AlertTriangle className="h-4 w-4" aria-hidden="true" />
            <AlertTitle>Konflik Pembaruan Data</AlertTitle>
            <AlertDescription className="mt-1 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <span>{message}</span>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    className="border-destructive/40 bg-background text-foreground hover:bg-muted min-h-10 shrink-0"
                    onClick={() => window.location.reload()}
                >
                    <RefreshCw className="mr-1.5 h-4 w-4" aria-hidden="true" />
                    Muat Ulang Halaman
                </Button>
            </AlertDescription>
        </Alert>
    );
}
