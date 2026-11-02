import '../../css/pages.css';

import SiteLayout from '@/components/site/site-layout';

export default function Legal({ title, paragraphs }: { title: string; paragraphs: string[] }) {
    return (
        <SiteLayout title={title}>
            <div className="tt-pg">
                <div className="tt-w">
                    <header className="tt-pg-head"><h1>{title}</h1></header>
                    <div className="tt-pg-prose">{paragraphs.map((p, i) => <p key={i}>{p}</p>)}</div>
                </div>
            </div>
        </SiteLayout>
    );
}
