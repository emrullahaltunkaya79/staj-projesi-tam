export function UrunKartiSkeleton() {
  return (
    <div className="animate-pulse">
      <div className="skeleton aspect-[4/5] rounded-2xl" />
      <div className="mt-4 space-y-2">
        <div className="skeleton h-4 w-3/4 rounded" />
        <div className="skeleton h-4 w-1/3 rounded" />
      </div>
    </div>
  );
}
