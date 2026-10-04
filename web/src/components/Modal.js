export default function Modal({ judul, onTutup, children }) {
  return (
    <div className="fixed inset-0 z-40 grid place-items-center bg-[#0e2a47]/40 p-4" onClick={onTutup}>
      <div className="max-h-[90vh] w-full max-w-lg overflow-auto rounded-2xl bg-white p-5 shadow-xl" onClick={(event) => event.stopPropagation()}>
        <div className="mb-4 flex items-center justify-between gap-3">
          <h2 className="text-2xl">{judul}</h2>
          <button type="button" className="text-sm text-[#5d6b7c]" onClick={onTutup}>
            Tutup
          </button>
        </div>
        {children}
      </div>
    </div>
  );
}
