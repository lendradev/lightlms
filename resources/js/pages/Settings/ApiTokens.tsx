import { Head, router, useForm } from '@inertiajs/react';
import { Copy, KeyRound, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { AppShell } from '@/components/AppShell';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Token = {
    id: number;
    name: string;
    last_used_at: string | null;
    created_at: string | null;
};

type Props = {
    tokens: Token[];
    issuedToken: string | null;
};

function formatDate(value: string | null) {
    if (!value) {
        return 'Never used';
    }

    return new Date(value).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
}

export default function ApiTokens({ tokens, issuedToken }: Props) {
    const { data, setData, post, processing, errors, reset } = useForm({ name: '' });
    const [copied, setCopied] = useState(false);

    const copy = async () => {
        if (!issuedToken) {
            return;
        }

        await navigator.clipboard.writeText(issuedToken);
        setCopied(true);
    };

    return (
        <>
            <Head title="API tokens" />
            <AppShell title="API tokens">
                <div className="mx-auto max-w-3xl space-y-6">
                    {issuedToken && (
                        <Card className="border-primary">
                            <CardHeader>
                                <CardTitle>Your new token</CardTitle>
                                <CardDescription>
                                    Copy it now. It is shown once and cannot be recovered later.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <code className="bg-muted block overflow-x-auto rounded-md border p-3 font-mono text-sm">
                                    {issuedToken}
                                </code>
                                <div className="flex gap-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={copy}
                                    >
                                        <Copy className="size-4" aria-hidden />
                                        {copied ? 'Copied' : 'Copy token'}
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => reset()}
                                    >
                                        Hide
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    <Card>
                        <CardHeader>
                            <CardTitle>Create a token</CardTitle>
                            <CardDescription>
                                A token acts on your behalf. Give it a name that says what it is
                                for, and revoke it when the integration stops.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    post('/settings/api-tokens', { preserveScroll: true });
                                }}
                                className="grid gap-4"
                            >
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Name</Label>
                                    <Input
                                        id="name"
                                        value={data.name}
                                        onChange={(event) => setData('name', event.target.value)}
                                        placeholder="Grading script"
                                        aria-describedby={errors.name ? 'name-error' : undefined}
                                        aria-invalid={errors.name ? true : undefined}
                                    />
                                    {errors.name && (
                                        <p id="name-error" className="text-destructive text-sm">
                                            {errors.name}
                                        </p>
                                    )}
                                </div>
                                <Button type="submit" disabled={processing}>
                                    <KeyRound className="size-4" aria-hidden />
                                    Create token
                                </Button>
                            </form>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Existing tokens</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {tokens.length === 0 ? (
                                <p className="text-muted-foreground py-8 text-center text-sm">
                                    No tokens yet. Create one to use the REST API.
                                </p>
                            ) : (
                                <ul className="divide-y">
                                    {tokens.map((token) => (
                                        <li key={token.id} className="flex items-center gap-4 py-3">
                                            <div className="min-w-0 flex-1">
                                                <p className="truncate text-sm font-medium">
                                                    {token.name}
                                                </p>
                                                <p className="text-muted-foreground text-xs">
                                                    Created {formatDate(token.created_at)} · Last
                                                    used {formatDate(token.last_used_at)}
                                                </p>
                                            </div>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                onClick={() =>
                                                    router.delete(
                                                        `/settings/api-tokens/${token.id}`,
                                                        { preserveScroll: true },
                                                    )
                                                }
                                            >
                                                <Trash2 className="size-4" aria-hidden />
                                                Revoke
                                                <span className="sr-only">{token.name}</span>
                                            </Button>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </AppShell>
        </>
    );
}
