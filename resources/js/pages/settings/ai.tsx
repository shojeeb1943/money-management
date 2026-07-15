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
};

export default function Ai({
    providers,
    provider,
    model,
    baseUrl,
    hasApiKey,
}: Props) {
    const [selectedProvider, setSelectedProvider] = useState(
        provider ?? Object.keys(providers)[0],
    );
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
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label>Provider</Label>
                                <Select
                                    value={selectedProvider}
                                    onValueChange={changeProvider}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select provider" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {Object.entries(providers).map(
                                            ([key, config]) => (
                                                <SelectItem
                                                    key={key}
                                                    value={key}
                                                >
                                                    {config.label}
                                                </SelectItem>
                                            ),
                                        )}
                                    </SelectContent>
                                </Select>
                                <input
                                    type="hidden"
                                    name="provider"
                                    value={selectedProvider}
                                />
                                <InputError message={errors.provider} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="ai-model">Model</Label>
                                {isCustom ? (
                                    <Input
                                        id="ai-model"
                                        name="model"
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
                                                    <SelectItem
                                                        key={m}
                                                        value={m}
                                                    >
                                                        {m}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <input
                                            type="hidden"
                                            name="model"
                                            value={selectedModel}
                                        />
                                    </>
                                )}
                                <InputError message={errors.model} />
                            </div>

                            {isCustom ? (
                                <div className="grid gap-2">
                                    <Label htmlFor="ai-base-url">
                                        Base URL
                                    </Label>
                                    <Input
                                        id="ai-base-url"
                                        name="base_url"
                                        defaultValue={baseUrl ?? ''}
                                        placeholder="https://api.example.com/v1"
                                    />
                                    <InputError message={errors.base_url} />
                                </div>
                            ) : null}

                            <div className="grid gap-2">
                                <Label htmlFor="ai-api-key">API key</Label>
                                <Input
                                    id="ai-api-key"
                                    name="api_key"
                                    type="password"
                                    autoComplete="off"
                                    placeholder={
                                        hasApiKey
                                            ? '•••• saved — leave blank to keep it'
                                            : 'Paste your API key'
                                    }
                                />
                                <InputError message={errors.api_key} />
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
