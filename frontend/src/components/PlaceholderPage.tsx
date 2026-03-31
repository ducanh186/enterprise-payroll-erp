interface PlaceholderPageProps {
  title: string;
}

export default function PlaceholderPage({ title }: PlaceholderPageProps) {
  return (
    <div className="p-8">
      <h1 className="text-2xl font-semibold text-slate-800 mb-2">{title}</h1>
      <div className="mt-6 rounded-xl border border-slate-200 bg-white p-10 text-center shadow-sm">
        <p className="text-slate-400 text-sm">
          Tính năng đang phát triển — chờ tích hợp API
        </p>
      </div>
    </div>
  );
}
