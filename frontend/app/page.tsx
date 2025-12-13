import { Skeleton } from "@/components/ui/skeleton"
import { Toolbar, ToolbarHeading } from "@/components/layouts/layout-7/components/toolbar";

export default function Page() {
  return (
    <>
      <Toolbar>
        <ToolbarHeading title="Dashboard" />
      </Toolbar>

      <div className="container">
        <Skeleton className="rounded-lg grow h-screen"></Skeleton>
      </div>
    </>
  );
}