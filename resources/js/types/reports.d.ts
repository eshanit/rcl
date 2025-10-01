interface Indicator {
  label: string;
  value: string;
}

type IIndicator = Readonly<Indicator>

export { type IIndicator }

//

interface Cohort {
  id: number;
  name: string;
}

type ICohort = Readonly<Cohort>

export { type ICohort}

//

interface Site {
  id: number;
  name: string;
  cohort_id: number;
}

type ISite = Readonly<Site>

export { type ISite }

//

interface Facility {
  id: number;
  name: string;
  site_id: number;
}

type IFacility = Readonly<Facility>

export { type IFacility }

//

interface BreakdownData {
  male: number;
  female: number;
  other: number;
  total: number;
}
 
type IBreakdownData = Readonly<BreakdownData>

export { type IBreakdownData }
//

interface ReportData {
  indicator: string;
  breakdown?: Record<string, IBreakdownData>;
  total?: number;
  percentage?: number;
  missed_visits?: number;
  total_visits?: number;
  retained?: number;
  total_pregnant?: number;
  coverage?:number;
  chart_data?: any;
}

type IReportData = Readonly<ReportData>

export { type IReportData }


