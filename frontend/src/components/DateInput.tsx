import { useEffect, useState, type InputHTMLAttributes } from "react";
import { displayDateToIso, isoToDisplayDate } from "../lib/format";

type DateInputProps = Omit<InputHTMLAttributes<HTMLInputElement>, "type" | "value" | "onChange"> & {
  value: string;
  onChange: (value: string) => void;
};

export default function DateInput({ value, onChange, className, ...props }: DateInputProps) {
  const [displayValue, setDisplayValue] = useState(() => isoToDisplayDate(value));

  useEffect(() => {
    setDisplayValue(isoToDisplayDate(value));
  }, [value]);

  return (
    <input
      {...props}
      type="text"
      inputMode="numeric"
      placeholder={props.placeholder ?? "DD/MM/YYYY"}
      value={displayValue}
      onChange={(event) => {
        const nextDisplay = event.target.value;
        setDisplayValue(nextDisplay);
        onChange(displayDateToIso(nextDisplay));
      }}
      className={className}
    />
  );
}
