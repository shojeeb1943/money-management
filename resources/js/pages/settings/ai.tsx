import { Form } from '@inertiajs/react';
import { useState } from 'react';
import AiController from '@/actions/App/Http/Controllers/Settings/AiController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { edit } from '@/routes/ai';

type ProviderConfig = {
    label: string;
    style: 'openai' | 'anthropic';
    base_url: string | null;
    models: string[];
};

type Props = {
    providers: Record<string, ProviderConfig>;
    provider: string | null;
    model: string | null;
    baseUrl: string | null;
    hasApiKey: boolean;
    fallbackProvider: string | null;
    fallbackModel: string | null;
    fallbackBaseUrl: string | null;
    hasFallbackApiKey: boolean;
};

const NONE = '__none__';

function ProviderFields({
    idPrefix,
    namePrefix,
    label,
    providers,
    allowNone,
    provider,
    model,
    baseUrl,
    hasApiKey,
    errors,
}: {
    idPrefix: string;
    namePrefix: string;
    label: string;
    providers: Record<string, ProviderConfig>;
    allowNone: boolean;
    provider: string | null;
    model: string | null;
    baseUrl: string | null;
    hasApiKey: boolean;
    errors: Record<string, string | undefined>;
}) {
    const [selectedProvider, setSelectedProvider] = useState(
        provider ?? (allowNone ? NONE : Object.keys(providers)[0]),
    );
    const isNone = allowNone && selectedProvider === NONE;
    const isCustom = selectedProvider === 'custom';
    const models = providers[selectedProvider]?.models ?? [];
    const [selectedModel, setSelectedModel] = useState(
        model && models.includes(model) ? model : (models[0] ?? ''),
    );

    const changeProvider = (next: string) => {
        setSelectedProvider(next);
        const nextModels = providers[next]?.models ?? [];
        setSelectedModel(
            next === provider && model ? model : (nextModels[0] ?? ''),
        );
    };

    return (
        <div className="space-y-6">
            <div className="grid gap-2">
                <Label>{label}</Label>
                <Select value={selectedProvider} onValueChange={changeProvider}>
                    <SelectTrigger>
                        <SelectValue placeholder="Select provider" />
                    </SelectTrigger>
                    <SelectContent>
                        {allowNone ? (
                            <SelectItem value={NONE}>None</SelectItem>
                        ) : null}
                        {Object.entries(providers).map(([key, config]) => (
                            <SelectItem key={key} value={key}>
                                {config.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <input
                    type="hidden"
                    name={`${namePrefix}provider`}
                    value={isNone ? '' : selectedProvider}
                />
                <InputError message={errors[`${namePrefix}provider`]} />
            </div>

            {!isNone ? (
                <>
                    <div className="grid gap-2">
                        <Label htmlFor={`${idPrefix}-model`}>Model</Label>
                        {isCustom ? (
                            <Input
                                id={`${idPrefix}-model`}
                                name={`${namePrefix}model`}
                                defaultValue={model ?? ''}
                                placeholder="e.g. my-custom-model"
                            />
                        ) : (
                            <>
                                <Select
                                    value={selectedModel}
                                    onValueChange={setSelectedModel}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select model" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {models.map((m) => (
                                            <SelectItem key={m} value={m}>
                                                {m}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <input
                                    type="hidden"
                                    name={`${namePrefix}model`}
                                    value={selectedModel}
                                />
                            </>
                        )}
                        <InputError message={errors[`${namePrefix}model`]} />
                    </div>

                    {isCustom ? (
                        <div className="grid gap-2">
                            <Label htmlFor={`${idPrefix}-base-url`}>
                                Base URL
                            </Label>
                            <Input
                                id={`${idPrefix}-base-url`}
                                name={`${namePrefix}base_url`}
                                defaultValue={baseUrl ?? ''}
                                placeholder="https://api.example.com/v1"
                            />
                            <InputError
                                message={errors[`${namePrefix}base_url`]}
                            />
                        </div>
                    ) : null}

                    <div className="grid gap-2">
                        <Label htmlFor={`${idPrefix}-api-key`}>API key</Label>
                        <Input
                            id={`${idPrefix}-api-key`}
                            name={`${namePrefix}api_key`}
                            type="password"
                            autoComplete="off"
                            placeholder={
                                hasApiKey
                                    ? '•••• saved — leave blank to keep it'
                                    : 'Paste your API key'
                            }
                        />
                        <InputError message={errors[`${namePrefix}api_key`]} />
                    </div>
                </>
            ) : null}
        </div>
    );
}

export default function Ai({
    providers,
    provider,
    model,
    baseUrl,
    hasApiKey,
    fallbackProvider,
    fallbackModel,
    fallbackBaseUrl,
    hasFallbackApiKey,
}: Props) {
    return (
        <>
            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="AI"
                    description="Configure the AI provider used to parse quick-add transaction text."
                />

                <Form
                    {...AiController.update.form()}
                    options={{ preserveScroll: true }}
                    className="space-y-8"
                >
                    {({ processing, errors }) => (
                        <>
                            <ProviderFields
                                idPrefix="ai"
                                namePrefix=""
                                label="Provider"
                                providers={providers}
                                allowNone={false}
                                provider={provider}
                                model={model}
                                baseUrl={baseUrl}
                                hasApiKey={hasApiKey}
                                errors={errors}
                            />

                            <div className="space-y-6 border-t pt-6">
                                <p className="text-sm text-muted-foreground">
                                    Fallback — used automatically if the primary
                                    provider fails (e.g. no balance or a rate
                                    limit).
                                </p>

                                <ProviderFields
                                    idPrefix="ai-fallback"
                                    namePrefix="fallback_"
                                    label="Fallback provider"
                                    providers={providers}
                                    allowNone
                                    provider={fallbackProvider}
                                    model={fallbackModel}
                                    baseUrl={fallbackBaseUrl}
                                    hasApiKey={hasFallbackApiKey}
                                    errors={errors}
                                />
                            </div>

                            <Button disabled={processing}>Save</Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

Ai.layout = {
    breadcrumbs: [
        {
            title: 'AI settings',
            href: edit(),
        },
    ],
};
