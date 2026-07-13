import { Form, Head } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import { Check, Copy, KeyRound, X } from 'lucide-react';
import { useState } from 'react';
import ConfirmDialog from '@/components/confirm-dialog';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { store, destroy } from '@/routes/api-tokens';

type Token = {
    id: string;
    name: string;
    createdAt: string | null;
    expiresAt: string | null;
};

type Props = {
    tokens: Token[];
    plainTextToken: string | null;
    mcpUrl: string;
};

export default function ApiTokens({ tokens, plainTextToken, mcpUrl }: Props) {
    const [revoking, setRevoking] = useState<Token | null>(null);
    const [copied, setCopied] = useState(false);

    const copyToken = () => {
        if (!plainTextToken) {
            return;
        }

        void navigator.clipboard.writeText(plainTextToken);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    const confirmRevoke = () => {
        if (!revoking) {
            return;
        }

        router.delete(destroy(revoking.id).url, {
            preserveScroll: true,
            onFinish: () => setRevoking(null),
        });
    };

    return (
        <div className="space-y-10">
            <Head title="API tokens" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="API tokens"
                    description="Tokens let AI agents and scripts access Moneta through the MCP server"
                />

                {plainTextToken ? (
                    <div className="space-y-2 rounded-lg border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-200/10 dark:bg-emerald-700/10">
                        <p className="text-sm font-medium text-emerald-700 dark:text-emerald-200">
                            Token created — copy it now. It will not be shown
                            again.
                        </p>
                        <div className="flex items-center gap-2">
                            <code className="flex-1 overflow-x-auto rounded bg-background px-3 py-2 font-mono text-xs">
                                {plainTextToken}
                            </code>
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={copyToken}
                            >
                                {copied ? (
                                    <Check className="size-4" />
                                ) : (
                                    <Copy className="size-4" />
                                )}
                                {copied ? 'Copied' : 'Copy'}
                            </Button>
                        </div>
                    </div>
                ) : null}

                <Form {...store.form()} resetOnSuccess className="space-y-4">
                    {({ errors, processing }) => (
                        <div className="flex items-end gap-3">
                            <div className="grid flex-1 gap-2 md:max-w-sm">
                                <Label htmlFor="token-name">Token name</Label>
                                <Input
                                    id="token-name"
                                    name="name"
                                    placeholder="e.g. Claude Code"
                                    required
                                />
                                <InputError message={errors.name} />
                            </div>
                            <Button type="submit" disabled={processing}>
                                Create token
                            </Button>
                        </div>
                    )}
                </Form>

                <div className="space-y-3">
                    {tokens.map((token) => (
                        <div
                            key={token.id}
                            className="flex items-center justify-between rounded-lg border p-4"
                        >
                            <div className="flex items-center gap-3">
                                <KeyRound className="size-4 text-muted-foreground" />
                                <div>
                                    <div className="font-medium">
                                        {token.name}
                                    </div>
                                    <div className="text-sm text-muted-foreground">
                                        Created {token.createdAt} ·{' '}
                                        {token.expiresAt
                                            ? `expires ${token.expiresAt}`
                                            : 'never expires'}
                                    </div>
                                </div>
                            </div>
                            <Button
                                variant="ghost"
                                size="sm"
                                aria-label={`Revoke ${token.name}`}
                                onClick={() => setRevoking(token)}
                            >
                                <X className="size-4" />
                            </Button>
                        </div>
                    ))}

                    {tokens.length === 0 ? (
                        <p className="py-4 text-sm text-muted-foreground">
                            No tokens yet.
                        </p>
                    ) : null}
                </div>
            </div>

            <div className="space-y-4">
                <Heading
                    variant="small"
                    title="Connect an AI agent"
                    description="Point any MCP client at the Moneta server with a bearer token"
                />
                <div className="space-y-3 rounded-lg border p-4 text-sm">
                    <p>
                        <span className="font-medium">MCP endpoint:</span>{' '}
                        <code className="rounded bg-muted px-1.5 py-0.5 font-mono text-xs">
                            {mcpUrl}
                        </code>
                    </p>
                    <p className="text-muted-foreground">
                        MCP clients that support OAuth (claude.ai and Claude
                        Desktop connectors) can connect with just the endpoint
                        URL — you approve access in the browser. For
                        header-based clients, use a token:
                    </p>
                    <code className="block overflow-x-auto rounded bg-muted px-3 py-2 font-mono text-xs whitespace-pre">
                        {`claude mcp add --transport http moneta ${mcpUrl} \\\n  --header "Authorization: Bearer <your-token>"`}
                    </code>
                </div>
            </div>

            <ConfirmDialog
                title={`Revoke "${revoking?.name}"?`}
                description="Agents using this token immediately lose access. This cannot be undone."
                confirmLabel="Revoke token"
                open={revoking !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setRevoking(null);
                    }
                }}
                onConfirm={confirmRevoke}
            />
        </div>
    );
}
