import PropTypes from 'prop-types'

export default function BasicDataItem({ label, value, className }) {
	return (
		<li className={`flex flex-col tracking-wider rounded-sm bg-neutral-3 p-2 text-sm ${className}`}>
			<span className='text-xs text-neutral-7'>{label}</span>
			<span className='font-bold'>{value}</span>
		</li>
	)
}

BasicDataItem.propTypes = {
	label: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
	value: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
	className: PropTypes.string
}

BasicDataItem.defaultProps = {
	label: undefined,
	value: undefined,
	className: ''
}
