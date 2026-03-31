import { useMemo } from "react";
import { useNavigate, useSearchParams } from "react-router-dom";

export type DetachedEditorAction = "create" | "edit";

const EDITOR_PARAM = "editor";
const RECORD_ID_PARAM = "recordId";

function normalizePath(pathname: string): string {
  return pathname.startsWith("/") ? pathname : `/${pathname}`;
}

export function openDetachedEditorTab(pathname: string, action: DetachedEditorAction, recordId?: string) {
  const targetPath = normalizePath(pathname);
  const url = new URL(targetPath, window.location.origin);
  url.searchParams.set(EDITOR_PARAM, action);

  if (recordId) {
    url.searchParams.set(RECORD_ID_PARAM, recordId);
  }

  window.open(`${url.pathname}${url.search}`, "_blank", "noopener,noreferrer");
}

export function useDetachedEditor(pathname: string) {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const targetPath = normalizePath(pathname);

  const editorAction = searchParams.get(EDITOR_PARAM);
  const editorId = searchParams.get(RECORD_ID_PARAM) ?? "";
  const isDetachedEditor = editorAction === "create" || editorAction === "edit";

  const detachedState = useMemo(
    () => ({
      isDetachedEditor,
      editorAction: editorAction as DetachedEditorAction | null,
      editorId,
    }),
    [editorAction, editorId, isDetachedEditor],
  );

  return {
    ...detachedState,
    openCreateTab: () => openDetachedEditorTab(targetPath, "create"),
    openEditTab: (recordId: string) => openDetachedEditorTab(targetPath, "edit", recordId),
    closeDetachedEditor: () => navigate(targetPath, { replace: true }),
  };
}
