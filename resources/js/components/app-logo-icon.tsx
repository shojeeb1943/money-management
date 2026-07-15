import type { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg">
            <path
                fillRule="evenodd"
                clipRule="evenodd"
                d="M20 40C31.0457 40 40 31.0457 40 20C40 8.9543 31.0457 0 20 0C8.9543 0 0 8.9543 0 20C0 31.0457 8.9543 40 20 40ZM20 36.5C29.1127 36.5 36.5 29.1127 36.5 20C36.5 10.8873 29.1127 3.5 20 3.5C10.8873 3.5 3.5 10.8873 3.5 20C3.5 29.1127 10.8873 36.5 20 36.5Z"
            />
            <text
                x="20"
                y="28"
                textAnchor="middle"
                fontFamily="Arial, sans-serif"
                fontWeight="bold"
                fontSize="19"
            >
                S
            </text>
        </svg>
    );
}
