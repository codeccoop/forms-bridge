// vendor
import React from "react";
import { Animate, Notice } from "@wordpress/components";

export default function Loading({ message }) {
  return (
    <Animate type="loading">
      {(className) => (
        <Notice className={className} status="success">
          {message}
        </Notice>
      )}
    </Animate>
  );
}
